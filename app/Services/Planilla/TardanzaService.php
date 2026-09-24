<?php

namespace App\Services\Planilla;

use App\Models\Employee;
use App\Models\PlanillaTardanza;
use App\Models\PlanillaPeriodo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Descuento por faltas y tardanzas — hoja «Dscto. Tard.» del Excel CAS.
 *
 * Fórmulas tal como están en el Excel:
 *   E  = REMUNERACIONES (ingresos del mes)
 *   F  = ROUND(E / 30, 2)                 valor por día
 *   G  = ROUND((F / 8) / 60, 2)           valor por minuto (8 h = 480 min)
 *   J  = F * días                         monto por días
 *   K  = G * minutos                      monto por minutos
 *   L  = J + K                            total a descontar
 *   N  = E - L                            base imponible (alimenta EsSalud/AFP)
 *
 * El registro es manual (origen MANUAL). El origen ASISTENCIA se llenará en
 * la Fase 7 al integrar con el módulo de Asistencias.
 */
class TardanzaService
{
    /** Jornada diaria en minutos: 8 horas → 480. */
    private const MINUTOS_JORNADA = 480;

    public function __construct(private PlanillaGenerador $generador)
    {
    }

    /**
     * Alta (o corrección) de un registro: calcula los importes y guarda el
     * snapshot de valor_dia / valor_minuto. Idempotente por
     * (periodo, empleado, fecha).
     */
    public function registrar(
        PlanillaPeriodo $periodo,
        Employee $employee,
        Carbon $fecha,
        int $dias,
        int $minutos,
        ?string $observacion = null
    ): PlanillaTardanza {
        $ingresos = $this->generador->ingresosVigentes($employee, $periodo->fechaCierre());
        $importes = $this->calcular($ingresos, $dias, $minutos);

        return PlanillaTardanza::updateOrCreate(
            [
                'periodo_id' => $periodo->id,
                'employee_id' => $employee->id,
                'fecha' => $fecha->toDateString(),
            ],
            [
                'dias' => $dias,
                'minutos' => $minutos,
                ...$importes,
                'observacion' => $observacion,
                'registrado_por' => Auth::id(),
            ]
        );
    }

    /**
     * Recalcula los importes de un registro existente con los ingresos
     * vigentes al cierre del periodo (se llama al modificar días/minutos).
     */
    public function recalcular(PlanillaTardanza $tardanza): PlanillaTardanza
    {
        $periodo = $tardanza->periodo;
        $ingresos = $this->generador->ingresosVigentes(
            $tardanza->employee,
            $periodo->fechaCierre()
        );

        $tardanza->update([
            ...$this->calcular($ingresos, (int) $tardanza->dias, (int) $tardanza->minutos),
        ]);

        return $tardanza;
    }

    /**
     * Importes de la hoja Excel para una remuneración y una cantidad dadas.
     *
     * @return array{valor_dia: float, valor_minuto: float, monto_dias: float, monto_minutos: float, total: float}
     */
    private function calcular(float $ingresos, int $dias, int $minutos): array
    {
        $valorDia = round($ingresos / 30, 2);
        $valorMinuto = round($valorDia / self::MINUTOS_JORNADA, 2);
        $montoDias = round($valorDia * $dias, 2);
        $montoMinutos = round($valorMinuto * $minutos, 2);

        return [
            'valor_dia' => $valorDia,
            'valor_minuto' => $valorMinuto,
            'monto_dias' => $montoDias,
            'monto_minutos' => $montoMinutos,
            'total' => round($montoDias + $montoMinutos, 2),
        ];
    }
}
