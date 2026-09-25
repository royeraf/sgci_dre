<?php

namespace App\Services\Planilla;

use App\Models\Employee;
use App\Models\Gratificacion;
use App\Models\GratificacionCasParametro;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GratificacionCasService
{
    public const PERIODO_JULIO = 'JULIO';

    public const PERIODO_DICIEMBRE = 'DICIEMBRE';

    private const ESSALUD_TASA = 0.09;

    private const MESES_SEMESTRE = 6;

    private const DIAS_MES = 30;

    public function obtenerParametro(int $anio): GratificacionCasParametro
    {
        $parametro = GratificacionCasParametro::vigente()
            ->where('anio_fiscal', $anio)
            ->first();

        if ($parametro === null) {
            $parametro = GratificacionCasParametro::vigente()
                ->where('anio_fiscal', '<=', $anio)
                ->orderByDesc('anio_fiscal')
                ->first();
        }

        if ($parametro === null) {
            throw new InvalidArgumentException("No existe porcentaje configurado para {$anio}");
        }

        return $parametro;
    }

    public function obtenerFechasPeriodo(int $anio, string $periodo): array
    {
        if ($periodo === self::PERIODO_JULIO) {
            return [
                'inicio' => Carbon::create($anio, 1, 1),
                'fin' => Carbon::create($anio, 6, 30),
                'corte' => Carbon::create($anio, 6, 30),
            ];
        }

        if ($periodo === self::PERIODO_DICIEMBRE) {
            return [
                'inicio' => Carbon::create($anio, 7, 1),
                'fin' => Carbon::create($anio, 12, 31),
                'corte' => Carbon::create($anio, 11, 30),
            ];
        }

        throw new InvalidArgumentException('Periodo inválido: use JULIO o DICIEMBRE');
    }

    public function calcularTiempoLaborado(
        ?Carbon $fechaIngreso,
        ?Carbon $fechaCese,
        Carbon $inicioPeriodo,
        Carbon $finPeriodo,
        ?Carbon $fechaVigenciaNorma = null
    ): array {
        $inicio = $inicioPeriodo->copy();

        if ($fechaVigenciaNorma !== null && $fechaVigenciaNorma->greaterThan($inicio)) {
            $inicio = $fechaVigenciaNorma->copy();
        }

        if ($fechaIngreso !== null && $fechaIngreso->greaterThan($inicio)) {
            $inicio = $fechaIngreso->copy();
        }

        $fin = $finPeriodo->copy();

        if ($fechaCese !== null && $fechaCese->lessThan($fin)) {
            $fin = $fechaCese->copy();
        }

        if ($inicio->greaterThan($fin)) {
            return ['meses' => 0, 'dias' => 0];
        }

        $finInclusive = $fin->copy()->addDay();
        $meses = (int) $inicio->diffInMonths($finInclusive);
        $ancla = $inicio->copy()->addMonthsNoOverflow($meses);
        $dias = (int) $ancla->diffInDays($finInclusive);

        return ['meses' => $meses, 'dias' => $dias];
    }

    public function calcular(
        float $remuneracion,
        int $anio,
        string $periodo,
        ?Carbon $fechaIngreso,
        ?Carbon $fechaCese = null
    ): array {
        $parametro = $this->obtenerParametro($anio);
        $fechas = $this->obtenerFechasPeriodo($anio, $periodo);

        $tiempo = $this->calcularTiempoLaborado(
            $fechaIngreso,
            $fechaCese,
            $fechas['inicio'],
            $fechas['fin'],
            $parametro->fecha_vigencia_norma
        );

        $porcentaje = (float) $parametro->porcentaje;
        $baseSemestre = round($remuneracion * $porcentaje, 2);
        $montoMes = $baseSemestre / self::MESES_SEMESTRE;
        $montoDia = $montoMes / self::DIAS_MES;

        $proporcional = ($montoMes * $tiempo['meses']) + ($montoDia * $tiempo['dias']);
        $minimo = $parametro->monto_minimo !== null ? (float) $parametro->monto_minimo : null;

        $montoFinal = $proporcional;

        if ($minimo !== null) {
            $montoFinal = max($proporcional, $minimo);
        }

        if ($tiempo['meses'] === 0 && $tiempo['dias'] === 0) {
            $montoFinal = 0.0;
        }

        return [
            'parametro' => $parametro,
            'meses_completos' => $tiempo['meses'],
            'dias' => $tiempo['dias'],
            'porcentaje_aplicado' => $porcentaje,
            'base_semestral' => round($baseSemestre, 2),
            'monto_proporcional' => round($proporcional, 2),
            'monto_minimo' => $minimo,
            'monto_final' => round($montoFinal, 2),
            'aporte_essalud' => round($montoFinal * self::ESSALUD_TASA, 2),
            'fecha_corte' => $fechas['corte'],
        ];
    }

    public function previsualizar(int $anio, string $periodo): array
    {
        $this->validarPeriodo($periodo);

        $calculos = [];

        foreach ($this->empleadosCas() as $empleado) {
            $calculo = $this->calcularParaEmpleado($empleado, $anio, $periodo);

            if ($calculo === null) {
                continue;
            }

            $calculos[] = $this->aFila($empleado, $anio, $periodo, $calculo);
        }

        return $calculos;
    }

    public function generar(int $anio, string $periodo, ?string $registradoPor = null): array
    {
        $this->validarPeriodo($periodo);

        return DB::transaction(function () use ($anio, $periodo, $registradoPor) {
            $generados = 0;
            $totalFinal = 0.0;
            $totalEssalud = 0.0;

            foreach ($this->empleadosCas() as $empleado) {
                $calculo = $this->calcularParaEmpleado($empleado, $anio, $periodo);

                if ($calculo === null) {
                    continue;
                }

                Gratificacion::updateOrCreate(
                    [
                        'employee_id' => $empleado->id,
                        'anio' => $anio,
                        'periodo' => $periodo,
                    ],
                    [
                        'remuneracion_corte' => $calculo['remuneracion_corte'],
                        'porcentaje_aplicado' => $calculo['porcentaje_aplicado'],
                        'meses_completos' => $calculo['meses_completos'],
                        'dias' => $calculo['dias'],
                        'base_semestral' => $calculo['base_semestral'],
                        'monto_proporcional' => $calculo['monto_proporcional'],
                        'monto_minimo' => $calculo['monto_minimo'],
                        'monto_final' => $calculo['monto_final'],
                        'aporte_essalud' => $calculo['aporte_essalud'],
                        'registrado_por' => $registradoPor,
                    ]
                );

                $totalFinal += $calculo['monto_final'];
                $totalEssalud += $calculo['aporte_essalud'];
                $generados++;
            }

            return [
                'empleados' => $generados,
                'total' => round($totalFinal, 2),
                'total_essalud' => round($totalEssalud, 2),
            ];
        });
    }

    public function listado(int $anio, string $periodo): array
    {
        $this->validarPeriodo($periodo);

        $gratificaciones = Gratificacion::with('employee.person')
            ->where('anio', $anio)
            ->where('periodo', $periodo)
            ->get()
            ->sortBy(fn ($g) => $g->employee?->apellidos ?? '', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return $gratificaciones->map(fn (Gratificacion $g) => [
            'id' => $g->id,
            'employee_id' => $g->employee_id,
            'dni' => $g->employee?->dni,
            'nombre_completo' => $g->employee?->full_name,
            'cargo' => $g->employee?->cargo,
            'fecha_ingreso' => optional($g->employee?->fecha_ingreso)->format('d/m/Y'),
            'remuneracion_corte' => (float) $g->remuneracion_corte,
            'porcentaje_aplicado' => (float) $g->porcentaje_aplicado,
            'meses_completos' => $g->meses_completos,
            'dias' => $g->dias,
            'base_semestral' => (float) $g->base_semestral,
            'monto_proporcional' => (float) $g->monto_proporcional,
            'monto_minimo' => $g->monto_minimo !== null ? (float) $g->monto_minimo : null,
            'monto_final' => (float) $g->monto_final,
            'aporte_essalud' => (float) $g->aporte_essalud,
        ])->all();
    }

    private function validarPeriodo(string $periodo): void
    {
        if (!in_array($periodo, [self::PERIODO_JULIO, self::PERIODO_DICIEMBRE], true)) {
            throw new InvalidArgumentException('Periodo inválido: use JULIO o DICIEMBRE');
        }
    }

    private function empleadosCas()
    {
        return Employee::with(['person', 'position', 'contractType', 'remunerations'])
            ->where('estado', 'ACTIVO')
            ->whereHas('contractType', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', ['CAS']);
            })
            ->get()
            ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function calcularParaEmpleado(Employee $empleado, int $anio, string $periodo): ?array
    {
        $generador = app(PlanillaGenerador::class);
        $fechas = $this->obtenerFechasPeriodo($anio, $periodo);
        $remuneracion = $generador->remuneracionVigente($empleado, $fechas['corte']);

        if ($remuneracion <= 0) {
            $antigua = $empleado->remunerations->sortBy('desde')->first();
            $remuneracion = $antigua ? round((float) $antigua->monto, 2) : 0.0;
        }

        if ($remuneracion <= 0) {
            return null;
        }

        $calculo = $this->calcular(
            $remuneracion,
            $anio,
            $periodo,
            $empleado->fecha_ingreso,
            null
        );

        $calculo['remuneracion_corte'] = $remuneracion;

        return $calculo;
    }

    private function aFila(Employee $empleado, int $anio, string $periodo, array $calculo): array
    {
        return [
            'employee_id' => $empleado->id,
            'dni' => $empleado->dni,
            'nombre_completo' => $empleado->full_name,
            'cargo' => $empleado->cargo,
            'fecha_ingreso' => $empleado->fecha_ingreso ? $empleado->fecha_ingreso->format('d/m/Y') : null,
            'remuneracion_corte' => $calculo['remuneracion_corte'],
            'porcentaje_aplicado' => $calculo['porcentaje_aplicado'],
            'meses_completos' => $calculo['meses_completos'],
            'dias' => $calculo['dias'],
            'base_semestral' => $calculo['base_semestral'],
            'monto_proporcional' => $calculo['monto_proporcional'],
            'monto_minimo' => $calculo['monto_minimo'],
            'monto_final' => $calculo['monto_final'],
            'aporte_essalud' => $calculo['aporte_essalud'],
        ];
    }
}
