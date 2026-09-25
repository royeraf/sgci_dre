<?php

namespace App\Services\Planilla;

use App\Models\Employee;
use App\Models\Gratificacion;
use App\Models\PlanillaComisionAfp;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaConceptoAsignacion;
use App\Models\PlanillaParametro;
use App\Models\PlanillaParametroAfp;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaRegimenPensionario;
use App\Models\PlanillaTardanza;
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

    private const CODIGO_GRATIFICACION = 'GRATIFICACION';

    /** Fallback si no existe fila en `planilla_parametros` (tope = 45% UIT 2026). */
    private const ESSALUD_TOPE = 2475.0;

    private const ESSALUD_TASA = 0.09;

    /** Fallback si no existe fila en `planilla_parametros_afp` (SBS 2026). */
    private const AFP_APORTE = 0.10;

    private const AFP_PRIMA = 0.0137;

    private const AFP_RMA = 12672.65;

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
        'GRATIFICACION',
    ];

    /** @var array<int, PlanillaParametro|null> */
    private array $parametrosPlanilla = [];

    /** @var array<string, PlanillaParametroAfp|null> */
    private array $parametrosAfp = [];

    /** @var array<string, PlanillaComisionAfp|null> */
    private array $comisionesAfp = [];

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
        $fecha = $periodo->fechaCierre();

        return DB::transaction(function () use ($periodo, $fecha) {
            $this->parametrosPlanilla = [];
            $this->parametrosAfp = [];
            $this->comisionesAfp = [];
            $this->limpiarDetalle($periodo);

            $conceptosActivos = PlanillaConcepto::where('activo', true)->get();
            $conceptos = $conceptosActivos->keyBy('codigo');
            $conceptosPorId = $conceptosActivos->keyBy('id');

            // Tardanzas no justificadas del periodo, agrupadas por empleado
            // (hoja «Dscto. Tard.»): descuentan y reducen la base imponible.
            $tardanzas = PlanillaTardanza::where('periodo_id', $periodo->id)
                ->noJustificadas()
                ->get()
                ->groupBy('employee_id');

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

                if ($base <= 0) {
                    continue;
                }

                $registros = $this->construirRegistros($empleado, $base, $fecha, $conceptos, $conceptosPorId, $tardanzas);

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

    public function remuneracionVigente(Employee $empleado, Carbon $fecha): float
    {
        $vigente = $empleado->remunerations
            ->filter(fn ($r) => $r->desde <= $fecha && (is_null($r->hasta) || $r->hasta >= $fecha))
            ->sortByDesc('desde')
            ->first();

        return $vigente ? round((float) $vigente->monto, 2) : 0.0;
    }

    /**
     * Registros del empleado: base, conceptos aplicables, tardanzas y
     * descuentos/aportes de ley.
     *
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @param Collection<string, PlanillaConcepto> $conceptosPorId
     * @param Collection<int, Collection<int, PlanillaTardanza>> $tardanzas No justificadas, por empleado.
     * @return array<int, array<string, mixed>>
     */
    private function construirRegistros(
        Employee $empleado,
        float $base,
        Carbon $fecha,
        Collection $conceptos,
        Collection $conceptosPorId,
        Collection $tardanzas = new Collection()
    ): array {
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

        // Gratificación de julio/diciembre (Ley 32563): se agrega como ingreso
        // con la misma fecha de corte del periodo.
        if ($registroGratificacion = $this->registroGratificacion($empleado, $fecha, $conceptos)) {
            $registros[] = $registroGratificacion;
        }

        // Faltas y tardanzas: se agregan ANTES de las retenciones para que
        // reduzcan la base imponible, igual que N = E - L en el Excel.
        if ($registroTardanza = $this->registroTardanzas($empleado, $conceptos, $tardanzas, $registros)) {
            $registros[] = $registroTardanza;
        }

        // Descuentos de ley (Fase 3)
        foreach ($this->retencionesPension($registros, $empleado, $conceptos, $fecha) as $registro) {
            $registros[] = $registro;
        }
        foreach ($this->aporteEssalud($registros, $conceptos, $fecha) as $registro) {
            $registros[] = $registro;
        }

        usort($registros, fn ($a, $b) => $a['orden'] <=> $b['orden']);

        return $registros;
    }

    /**
     * E de la hoja Excel: remuneración base vigente + conceptos INGRESO
     * aplicables (asignaciones y catálogo) resueltos en la fecha dada.
     */
    public function ingresosVigentes(Employee $empleado, Carbon $fecha, ?Collection $conceptosPorId = null): float
    {
        $base = $this->remuneracionVigente($empleado, $fecha);
        $conceptosPorId ??= PlanillaConcepto::where('activo', true)->get()->keyBy('id');

        $total = $base;

        foreach ($this->resolverConceptos($empleado, $fecha, $conceptosPorId) as $aplicable) {
            $concepto = $aplicable['concepto'];

            if ($concepto->tipo !== 'INGRESO') {
                continue;
            }

            $asignacion = $aplicable['asignacion'];
            $total += $asignacion
                ? $this->calcularMonto($asignacion, $base, $concepto)
                : $this->montoCatalogo($concepto, $base);
        }

        return round($total, 2);
    }

    /**
     * Ingreso de gratificación (julio → Fiestas Patrias, diciembre → Navidad).
     * Usa el registro guardado en la pestaña «Gratificaciones»; si no existe,
     * calcula al vuelo con el mismo servicio para que la planilla siempre
     * refleje el monto.
     *
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @return array<string, mixed>|null
     */
    private function registroGratificacion(Employee $empleado, Carbon $fecha, Collection $conceptos): ?array
    {
        $periodoGratificacion = match ((int) $fecha->month) {
            7 => GratificacionCasService::PERIODO_JULIO,
            12 => GratificacionCasService::PERIODO_DICIEMBRE,
            default => null,
        };

        if ($periodoGratificacion === null) {
            return null;
        }

        $concepto = $conceptos->get(self::CODIGO_GRATIFICACION);

        if (!$concepto || !$concepto->activo) {
            return null;
        }

        $gratificacion = Gratificacion::where('employee_id', $empleado->id)
            ->where('anio', $fecha->year)
            ->where('periodo', $periodoGratificacion)
            ->first();

        if ($gratificacion !== null) {
            $monto = (float) $gratificacion->monto_final;
            $base = (float) $gratificacion->base_semestral;
            $porcentaje = (float) $gratificacion->porcentaje_aplicado;
        } else {
            $calculo = app(GratificacionCasService::class)
                ->calcularParaEmpleado($empleado, $fecha->year, $periodoGratificacion);

            if ($calculo === null) {
                return null;
            }

            $monto = (float) $calculo['monto_final'];
            $base = (float) $calculo['base_semestral'];
            $porcentaje = (float) $calculo['porcentaje_aplicado'];
        }

        if ($monto <= 0) {
            return null;
        }

        return $this->registro($concepto, 'INGRESO', $monto, $base, $porcentaje);
    }

    /**
     * Línea Faltas/Tardanzas (L) a partir de los registros no justificados del
     * periodo. La base guardada es N = E - L, la base imponible del Excel.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param Collection<int, Collection<int, PlanillaTardanza>> $tardanzas
     * @return array<string, mixed>|null
     */
    private function registroTardanzas(
        Employee $empleado,
        Collection $conceptos,
        Collection $tardanzas,
        array $registros
    ): ?array {
        $grupo = $tardanzas->get($empleado->id);

        if (!$grupo) {
            return null;
        }

        $total = round((float) $grupo->sum('total'), 2);

        if ($total <= 0) {
            return null;
        }

        $concepto = $conceptos->get('FALTAS_TARDANZAS');

        if (!$concepto) {
            return null;
        }

        $baseImponible = round($this->sumar($registros, 'INGRESO') - $total, 2);

        return $this->registro($concepto, 'DESCUENTO', $total, $baseImponible, null);
    }

    /**
     * AFP u ONP según el perfil de pensión del empleado.
     *
     * AFP: aporte obligatorio, prima y comisión se toman de los parámetros
     * SBS vigentes por mes de devengue (`planilla_parametros_afp` /
     * `planilla_comisiones_afp`), con tope de remuneración máxima asegurable.
     * Solo el tipo de comisión FLUJO descuenta en planilla; MIXTA y SALDO no
     * generan retención mensual.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @return array<int, array<string, mixed>>
     */
    private function retencionesPension(array $registros, Employee $empleado, Collection $conceptos, Carbon $fecha): array
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
                $ajuste = $base < 2000 ? 0.01 : 0.02;
                $monto = $this->dinero($base * $tasa) + $ajuste;
                $items[] = $this->registro($concepto, 'DESCUENTO', $monto, $base, $tasa);
            }

            return $items;
        }

        // AFP: fondo + seguro + comisión (con tope de remuneración máxima asegurable)
        $parametro = $this->parametroAfp($fecha);
        $rma = $parametro !== null ? (float) $parametro->remuneracion_maxima_asegurable : self::AFP_RMA;
        $base = min($this->baseAfecta($registros, 'afecto_afp'), $rma);
        if ($base <= 0) {
            return $items;
        }

        $tasaFondo = $parametro !== null ? (float) $parametro->aporte_obligatorio : self::AFP_APORTE;
        if ($concepto = $conceptos->get('AFP_FONDO')) {
            $items[] = $this->registro($concepto, 'DESCUENTO', $base * $tasaFondo, $base, $tasaFondo);
        }

        $tasaSeguro = $parametro !== null ? (float) $parametro->prima_seguro : self::AFP_PRIMA;
        if ($concepto = $conceptos->get('AFP_SEGURO')) {
            $items[] = $this->registro($concepto, 'DESCUENTO', $base * $tasaSeguro, $base, $tasaSeguro);
        }

        $tasaComision = 0.0;
        if ($empleado->payrollProfile?->tipo_comision === 'FLUJO') {
            $comision = $this->comisionAfp($fecha, $regimen);
            $tasaComision = $comision !== null ? (float) $comision->comision_flujo : 0.0;
        }

        if ($tasaComision > 0 && ($concepto = $conceptos->get('AFP_COMISION'))) {
            $items[] = $this->registro($concepto, 'DESCUENTO', $base * $tasaComision, $base, $tasaComision);
        }

        return $items;
    }

    /**
     * EsSalud 9% (aporte del empleador) con tope de base según
     * `planilla_parametros` del año: tope = UIT × %tope, mínimo = RMV.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param Collection<string, PlanillaConcepto> $conceptos
     * @return array<int, array<string, mixed>>
     */
    private function aporteEssalud(array $registros, Collection $conceptos, Carbon $fecha): array
    {
        $concepto = $conceptos->get('ESSALUD');
        if (!$concepto) {
            return [];
        }

        $base = $this->baseAfecta($registros, 'afecto_essalud');
        if ($base <= 0) {
            return [];
        }

        $parametro = $this->parametroPlanilla((int) $fecha->year);
        $tope = $parametro !== null ? $parametro->topeEssalud() : self::ESSALUD_TOPE;
        $tasa = $parametro !== null ? (float) $parametro->tasa_essalud : self::ESSALUD_TASA;
        $rmv = $parametro?->rmv !== null ? (float) $parametro->rmv : null;

        $baseCalculo = min($base, $tope);
        if ($rmv !== null) {
            $baseCalculo = max($baseCalculo, min($rmv, $tope));
        }

        return [
            $this->registro($concepto, 'APORTACION', $baseCalculo * $tasa, $baseCalculo, $tasa),
        ];
    }

    /**
     * Parámetros de planilla del año (UIT, %tope, RMV, tasa), memorizados
     * por año para no consultar por empleado.
     */
    private function parametroPlanilla(int $anio): ?PlanillaParametro
    {
        if (!array_key_exists($anio, $this->parametrosPlanilla)) {
            $this->parametrosPlanilla[$anio] = PlanillaParametro::vigente($anio);
        }

        return $this->parametrosPlanilla[$anio];
    }

    /**
     * Parámetros SBS del mes de devengue (aporte, prima, RMA), memorizados.
     */
    private function parametroAfp(Carbon $fecha): ?PlanillaParametroAfp
    {
        $clave = $fecha->format('Y-m');

        if (!array_key_exists($clave, $this->parametrosAfp)) {
            $this->parametrosAfp[$clave] = PlanillaParametroAfp::vigente($fecha);
        }

        return $this->parametrosAfp[$clave];
    }

    /**
     * Comisión SBS de la AFP en el mes de devengue (flujo/saldo), memorizada.
     */
    private function comisionAfp(Carbon $fecha, PlanillaRegimenPensionario $regimen): ?PlanillaComisionAfp
    {
        $clave = $fecha->format('Y-m') . '|' . $regimen->id;

        if (!array_key_exists($clave, $this->comisionesAfp)) {
            $this->comisionesAfp[$clave] = PlanillaComisionAfp::where('mes', $fecha->copy()->startOfMonth())
                ->where('regimen_pensionario_id', $regimen->id)
                ->first();
        }

        return $this->comisionesAfp[$clave];
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
     * Redondeo a 2 decimales como el de Excel: normaliza a 15 dígitos
     * significativos antes de redondear, para que productos exactos en
     * decimal (p. ej. 1890 × 1.55% = 29.295) no caigan por debajo por
     * el ruido del punto flotante (29.294999999999998 → 29.30, no 29.29).
     */
    private function dinero(float $valor): float
    {
        $decimales = max(0, 14 - (int) floor(log10(abs($valor) ?: 1e-9)));

        return round(round($valor, $decimales), 2);
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
            'monto' => $this->dinero($monto),
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
