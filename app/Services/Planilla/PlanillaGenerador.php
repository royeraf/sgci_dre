<?php

namespace App\Services\Planilla;

use App\Models\Employee;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaConceptoAsignacion;
use App\Models\PlanillaPeriodo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Motor de cálculo de planilla CAS.
 *
 * Genera, para un periodo mensual, un `planilla_detalles` por empleado CAS
 * activo con sus `planilla_detalle_items` a partir de:
 *  - la remuneración base vigente (`employee_remunerations`),
 *  - las asignaciones de conceptos (`planilla_concepto_asignaciones`),
 *    por régimen o por empleado, y
 *  - los descuentos/aportes de ley: AFP/ONP (según el perfil de pensión) y
 *    EsSalud 9% (aporte del empleador).
 */
class PlanillaGenerador
{
    private const CODIGO_BASE = 'REM_DL1057';

    /** Tope de base para EsSalud observado en la planilla CAS (Planilla 0042). */
    private const ESSALUD_TOPE = 2475.0;

    private const ESSALUD_TASA = 0.09;

    private const CONCEPTOS_LEY = [
        'REM_DL1057',
        'ONP_19990',
        'AFP_FONDO',
        'AFP_SEGURO',
        'AFP_COMISION',
        'ESSALUD',
    ];

    /**
     * Conceptos con lógica propia del motor: no se derivan del catálogo ni de
     * las asignaciones, para no duplicarlos (o adelantar fases pendientes como
     * la renta).
     */
    private const CONCEPTOS_GESTIONADOS = [
        'REM_DL1057',
        'ONP_19990',
        'AFP_FONDO',
        'AFP_SEGURO',
        'AFP_COMISION',
        'ESSALUD',
        'FALTAS_TARDANZAS',
        'RENTA_4TA',
    ];

    /**
     * (Re)genera la planilla del periodo. Solo en BORRADOR o CALCULADA.
     */
    public function generar(PlanillaPeriodo $periodo): array
    {
        if (!$periodo->editable) {
            throw new RuntimeException(
                "La planilla está en estado {$periodo->estado} y no se puede recalcular."
            );
        }

        // La remuneración/asignaciones vigentes se resuelven al cierre del periodo
        // (último día del mes). Usar el primer día falla cuando una remuneración
        // se registra a mitad de mes (p. ej. una base con `desde` posterior al día 1).
        $fecha = ($periodo->fecha_fin ?? Carbon::create($periodo->anio, $periodo->mes, 1)->endOfMonth())
            ->copy()
            ->startOfDay();

        return DB::transaction(function () use ($periodo, $fecha) {
            $this->limpiarDetalle($periodo);

            $conceptosActivos = PlanillaConcepto::where('activo', true)->get();
            $conceptos = $conceptosActivos->keyBy('codigo');
            $conceptosPorId = $conceptosActivos->keyBy('id');

            $empleados = Employee::with([
                'person',
                'contractType',
                'remunerations',
                'payrollProfile.regimenPensionario',
            ])
                ->where('estado', 'ACTIVO')
                ->whereHas('contractType', function ($query) {
                    $query->whereRaw('UPPER(nombre) = ?', ['CAS']);
                })
                ->get()
                ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            $totalNeto = 0.0;
            $procesados = 0;

            foreach ($empleados as $empleado) {
                $base = $this->remuneracionVigente($empleado, $fecha);
                $registros = $this->construirRegistros($empleado, $base, $fecha, $conceptos, $conceptosPorId);

                $totalIngresos = $this->sumar($registros, 'INGRESO');
                $totalDescuentos = $this->sumar($registros, 'DESCUENTO');
                $totalAportaciones = $this->sumar($registros, 'APORTACION');
                $neto = round($totalIngresos - $totalDescuentos, 2);

                $detalle = $periodo->detalles()->create([
                    'employee_id' => $empleado->id,
                    'remuneracion_base' => $base,
                    'total_ingresos' => $totalIngresos,
                    'total_descuentos' => $totalDescuentos,
                    'total_aportaciones' => $totalAportaciones,
                    'neto_pagar' => $neto,
                ]);

                if (!empty($registros)) {
                    $detalle->items()->createMany(array_map([$this, 'aItemDb'], $registros));
                }

                $totalNeto += $neto;
                $procesados++;
            }

            $periodo->update([
                'estado' => 'CALCULADA',
                'total_empleados' => $procesados,
                'total_neto' => round($totalNeto, 2),
            ]);

            return [
                'empleados' => $procesados,
                'total_neto' => round($totalNeto, 2),
            ];
        });
    }

    private function limpiarDetalle(PlanillaPeriodo $periodo): void
    {
        $periodo->detalles()->each(function ($detalle) {
            $detalle->items()->delete();
        });
        $periodo->detalles()->delete();
    }

    private function remuneracionVigente(Employee $empleado, Carbon $fecha): float
    {
        $vigente = $empleado->remunerations
            ->filter(fn ($r) => $r->desde <= $fecha && (is_null($r->hasta) || $r->hasta >= $fecha))
            ->sortByDesc('desde')
            ->first();

        return $vigente ? round((float) $vigente->monto, 2) : 0.0;
    }

    /**
     * Registros del empleado: base, conceptos aplicables y descuentos/aportes de ley.
     *
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @param Collection<string, PlanillaConcepto> $conceptosPorId
     * @return array<int, array<string, mixed>>
     */
    private function construirRegistros(Employee $empleado, float $base, Carbon $fecha, Collection $conceptos, Collection $conceptosPorId): array
    {
        $registros = [];

        $conceptoBase = $conceptos->get(self::CODIGO_BASE);
        if ($conceptoBase && $base > 0) {
            $registros[] = $this->registro($conceptoBase, 'INGRESO', $base, $base, null);
        }

        foreach ($this->resolverConceptos($empleado, $fecha, $conceptosPorId) as $aplicable) {
            $concepto = $aplicable['concepto'];
            $asignacion = $aplicable['asignacion'];

            $esPorcentaje = $asignacion
                ? $asignacion->porcentaje !== null || $concepto->es_porcentaje
                : (bool) $concepto->es_porcentaje;

            $monto = $asignacion
                ? $this->calcularMonto($asignacion, $base, $concepto)
                : $this->montoCatalogo($concepto, $base);

            $registros[] = $this->registro(
                $concepto,
                $concepto->tipo,
                $monto,
                $esPorcentaje ? $base : 0,
                $asignacion
                    ? ($asignacion->porcentaje ?? ($concepto->es_porcentaje ? (float) $concepto->valor : null))
                    : ($concepto->es_porcentaje ? (float) $concepto->valor : null)
            );
        }

        // Descuentos de ley (Fase 3)
        foreach ($this->retencionesPension($registros, $empleado, $conceptos) as $registro) {
            $registros[] = $registro;
        }
        foreach ($this->aporteEssalud($registros, $conceptos) as $registro) {
            $registros[] = $registro;
        }

        usort($registros, fn ($a, $b) => $a['orden'] <=> $b['orden']);

        return $registros;
    }

    /**
     * AFP u ONP según el perfil de pensión del empleado.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @return array<int, array<string, mixed>>
     */
    private function retencionesPension(array $registros, Employee $empleado, Collection $conceptos): array
    {
        $regimen = $empleado->payrollProfile?->regimenPensionario;

        if (!$regimen) {
            return [];
        }

        $items = [];

        if ($regimen->tipo === 'ONP') {
            $base = $this->baseAfecta($registros, 'afecto_onp');
            $concepto = $conceptos->get('ONP_19990');
            if ($concepto && $base > 0) {
                $tasa = (float) $regimen->aporte_obligatorio;
                $items[] = $this->registro($concepto, 'DESCUENTO', $base * $tasa, $base, $tasa);
            }

            return $items;
        }

        // AFP: fondo + seguro + comisión
        $base = $this->baseAfecta($registros, 'afecto_afp');
        if ($base <= 0) {
            return $items;
        }

        $tasaFondo = (float) $regimen->aporte_obligatorio;
        if ($concepto = $conceptos->get('AFP_FONDO')) {
            $items[] = $this->registro($concepto, 'DESCUENTO', $base * $tasaFondo, $base, $tasaFondo);
        }

        $tasaSeguro = (float) $regimen->prima_seguro;
        if ($concepto = $conceptos->get('AFP_SEGURO')) {
            $items[] = $this->registro($concepto, 'DESCUENTO', $base * $tasaSeguro, $base, $tasaSeguro);
        }

        $comision = $regimen->comision_fija ?? $regimen->comision_mixta ?? $regimen->comision_flujo;
        if ($comision !== null && (float) $comision > 0 && ($concepto = $conceptos->get('AFP_COMISION'))) {
            $items[] = $this->registro($concepto, 'DESCUENTO', $base * (float) $comision, $base, (float) $comision);
        }

        return $items;
    }

    /**
     * EsSalud 9% (aporte del empleador) con tope de base.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @return array<int, array<string, mixed>>
     */
    private function aporteEssalud(array $registros, Collection $conceptos): array
    {
        $concepto = $conceptos->get('ESSALUD');
        if (!$concepto) {
            return [];
        }

        $base = $this->baseAfecta($registros, 'afecto_essalud');
        if ($base <= 0) {
            return [];
        }

        $baseCalculo = min($base, self::ESSALUD_TOPE);

        return [
            $this->registro($concepto, 'APORTACION', $baseCalculo * self::ESSALUD_TASA, $baseCalculo, self::ESSALUD_TASA),
        ];
    }

    /**
     * Base afecta: suma de ingresos afectos menos descuentos afectos.
     *
     * @param array<int, array<string, mixed>> $registros
     */
    private function baseAfecta(array $registros, string $flag): float
    {
        $base = array_reduce($registros, function ($carry, $registro) use ($flag) {
            if (empty($registro[$flag])) {
                return $carry;
            }
            if ($registro['tipo'] === 'INGRESO') {
                return $carry + $registro['monto'];
            }
            if ($registro['tipo'] === 'DESCUENTO') {
                return $carry - $registro['monto'];
            }

            return $carry;
        }, 0.0);

        return round(max($base, 0), 2);
    }

    /**
     * Conceptos aplicables al empleado, por precedencia:
     *   1. asignación por empleado,
     *   2. asignación por régimen (tipo de contrato),
     *   3. valor por defecto del catálogo (`planilla_conceptos.valor`),
     *      que es lo que permite que un concepto nuevo con monto se aplique
     *      a todo el personal sin crear asignaciones una por una.
     *
     * Los conceptos con lógica propia del motor se excluyen para no duplicarlos.
     *
     * @param Collection<string, PlanillaConcepto> $conceptosPorId
     * @return Collection<int, array{concepto: PlanillaConcepto, asignacion: PlanillaConceptoAsignacion|null}>
     */
    private function resolverConceptos(Employee $empleado, Carbon $fecha, Collection $conceptosPorId): Collection
    {
        $asignaciones = PlanillaConceptoAsignacion::with('concepto')
            ->where('activo', true)
            ->where(function ($query) use ($empleado) {
                $query->where('employee_id', $empleado->id)
                    ->orWhere(function ($sub) use ($empleado) {
                        $sub->whereNull('employee_id')
                            ->where('contract_type_id', $empleado->contract_type_id);
                    });
            })
            ->where(function ($query) use ($fecha) {
                $query->whereNull('desde')->orWhere('desde', '<=', $fecha);
            })
            ->where(function ($query) use ($fecha) {
                $query->whereNull('hasta')->orWhere('hasta', '>=', $fecha);
            })
            ->get()
            ->filter(fn ($asignacion) => $asignacion->concepto
                && !in_array($asignacion->concepto->codigo, self::CONCEPTOS_GESTIONADOS, true))
            ->groupBy('concepto_id')
            ->map(fn ($grupo) => $grupo->firstWhere('employee_id', '!=', null) ?? $grupo->first());

        $resultado = $asignaciones->map(fn ($asignacion) => [
            'concepto' => $asignacion->concepto,
            'asignacion' => $asignacion,
        ]);

        foreach ($conceptosPorId as $concepto) {
            if ($resultado->has($concepto->id)) {
                continue;
            }

            if ($concepto->valor === null || in_array($concepto->codigo, self::CONCEPTOS_GESTIONADOS, true)) {
                continue;
            }

            $resultado->put($concepto->id, ['concepto' => $concepto, 'asignacion' => null]);
        }

        return $resultado->values();
    }

    /**
     * Valor por defecto del catálogo: monto fijo o porcentaje sobre la
     * remuneración base.
     */
    private function montoCatalogo(PlanillaConcepto $concepto, float $base): float
    {
        if ($concepto->es_porcentaje) {
            return round($base * (float) $concepto->valor, 2);
        }

        return round((float) $concepto->valor, 2);
    }

    private function calcularMonto(PlanillaConceptoAsignacion $asignacion, float $base, PlanillaConcepto $concepto): float
    {
        if ($asignacion->porcentaje !== null) {
            return round($base * (float) $asignacion->porcentaje, 2);
        }
        if ($asignacion->monto !== null) {
            return round((float) $asignacion->monto, 2);
        }
        if ($concepto->es_porcentaje && $concepto->valor !== null) {
            return round($base * (float) $concepto->valor, 2);
        }
        if ($concepto->valor !== null) {
            return round((float) $concepto->valor, 2);
        }

        return 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    private function registro(PlanillaConcepto $concepto, string $tipo, float $monto, float $base, ?float $porcentaje): array
    {
        return [
            'concepto_id' => $concepto->id,
            'tipo' => $tipo,
            'descripcion' => $concepto->nombre,
            'base_calculo' => round($base, 2),
            'porcentaje' => $porcentaje,
            'monto' => round($monto, 2),
            'orden' => $concepto->orden,
            'afecto_renta5' => (bool) $concepto->afecto_renta5,
            'afecto_essalud' => (bool) $concepto->afecto_essalud,
            'afecto_onp' => (bool) $concepto->afecto_onp,
            'afecto_afp' => (bool) $concepto->afecto_afp,
        ];
    }

    /**
     * @param array<string, mixed> $registro
     * @return array<string, mixed>
     */
    private function aItemDb(array $registro): array
    {
        return [
            'concepto_id' => $registro['concepto_id'],
            'tipo' => $registro['tipo'],
            'descripcion' => $registro['descripcion'],
            'base_calculo' => $registro['base_calculo'],
            'porcentaje' => $registro['porcentaje'],
            'monto' => $registro['monto'],
            'orden' => $registro['orden'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $registros
     */
    private function sumar(array $registros, string $tipo): float
    {
        return round(array_reduce(
            $registros,
            fn ($carry, $registro) => $registro['tipo'] === $tipo ? $carry + $registro['monto'] : $carry,
            0.0
        ), 2);
    }
}
