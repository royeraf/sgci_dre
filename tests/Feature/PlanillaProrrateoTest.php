<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRemuneration;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaConceptoAsignacion;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Prorrateo de la remuneración básica cuando el contrato empieza o termina
 * a mitad de mes (planilla_detalles.dias_pagados, base 30).
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaProrrateoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_contrato_que_termina_a_mitad_de_mes_proporcionaliza_la_basica(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        // Cesa el día 15 del periodo (15 días de solape de 30).
        $empleado->forceFill([
            'fecha_ingreso' => $periodo->fecha_inicio->copy()->subDays(90)->toDateString(),
            'fecha_inicio_contrato' => null,
            'fecha_fin_contrato' => $periodo->fecha_inicio->copy()->addDays(14)->toDateString(),
        ])->save();

        $base = $this->baseVigente($empleado, $periodo);
        $basicaEsperada = $this->proporcional($base, 15);

        $this->generar($periodo);

        $detalle = PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->firstOrFail();

        $this->assertSame(15, $detalle->dias_pagados);
        $this->assertEqualsWithDelta($base, (float) $detalle->remuneracion_base, 0.01);
        $this->assertEqualsWithDelta($basicaEsperada, $this->montoConcepto($detalle, 'REM_DL1057'), 0.01);
        $this->assertLessThan($base, $this->montoConcepto($detalle, 'REM_DL1057'));
    }

    /**
     * Réplica del Excel (Planilla 0042, «León Chamoli», Del 30-07-26 al 29-08-26):
     * en un mes de 31 días el contrato que termina el 29 paga 29/30 (no 28/31)
     * y TODOS los ingresos fijos se descuentan 1 día, no solo la básica.
     */
    public function test_cese_dia_29_paga_29_de_30_y_prorratea_todos_los_ingresos(): void
    {
        $periodo = $this->periodoLibre();
        $this->assertSame(31, (int) $periodo->fecha_fin->daysInMonth);
        $empleado = $this->empleadoCas($periodo);

        $empleado->forceFill([
            'fecha_ingreso' => $periodo->fecha_inicio->copy()->subDays(90)->toDateString(),
            'fecha_inicio_contrato' => null,
            'fecha_fin_contrato' => $periodo->fecha_inicio->copy()->addDays(28)->toDateString(),
        ])->save();

        $base = $this->baseVigente($empleado, $periodo);

        $this->generar($periodo);

        $detalle = PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->firstOrFail();

        $this->assertSame(29, $detalle->dias_pagados);
        $this->assertEqualsWithDelta($this->proporcional($base, 29), $this->montoConcepto($detalle, 'REM_DL1057'), 0.01);
        $this->assertEqualsWithDelta($this->proporcional(64.19, 29), $this->montoConcepto($detalle, 'DS311_2022'), 0.01);
        $this->assertEqualsWithDelta($this->proporcional(50, 29), $this->montoConcepto($detalle, 'DS313_2023'), 0.01);
    }

    /**
     * El reintegro (día no pagado del mes anterior) entra como ingreso manual
     * íntegro: nunca se proporcionaliza por los días del mes en curso.
     */
    public function test_reintegro_se_paga_integro_sin_prorratear(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $empleado->forceFill([
            'fecha_ingreso' => $periodo->fecha_inicio->copy()->subDays(90)->toDateString(),
            'fecha_inicio_contrato' => null,
            'fecha_fin_contrato' => $periodo->fecha_inicio->copy()->addDays(28)->toDateString(),
        ])->save();

        $concepto = PlanillaConcepto::where('codigo', 'REINTEGRO')->firstOrFail();

        PlanillaConceptoAsignacion::create([
            'concepto_id' => $concepto->id,
            'employee_id' => $empleado->id,
            'monto' => 88.31,
            'activo' => true,
        ]);

        $this->generar($periodo);

        $detalle = PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->firstOrFail();

        $this->assertSame(29, $detalle->dias_pagados);
        $this->assertEqualsWithDelta(88.31, $this->montoConcepto($detalle, 'REINTEGRO'), 0.01);
    }

    public function test_contrato_vencido_antes_del_periodo_excluye_al_empleado(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $empleado->forceFill([
            'fecha_ingreso' => $periodo->fecha_inicio->copy()->subDays(90)->toDateString(),
            'fecha_inicio_contrato' => null,
            'fecha_fin_contrato' => $periodo->fecha_inicio->copy()->subDay()->toDateString(),
        ])->save();

        $this->generar($periodo);

        $this->assertNull(
            PlanillaDetalle::where('periodo_id', $periodo->id)
                ->where('employee_id', $empleado->id)
                ->first()
        );
    }

    public function test_contrato_indefinido_paga_mes_completo(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $empleado->forceFill([
            'fecha_ingreso' => $periodo->fecha_inicio->copy()->subDays(90)->toDateString(),
            'fecha_inicio_contrato' => null,
            'fecha_fin_contrato' => null,
        ])->save();

        $base = $this->baseVigente($empleado, $periodo);

        $this->generar($periodo);

        $detalle = PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->firstOrFail();

        $this->assertSame(30, $detalle->dias_pagados);
        $this->assertEqualsWithDelta($base, $this->montoConcepto($detalle, 'REM_DL1057'), 0.01);
    }

    public function test_el_detalle_expone_los_dias_pagados(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $empleado->forceFill([
            'fecha_ingreso' => $periodo->fecha_inicio->copy()->subDays(90)->toDateString(),
            'fecha_inicio_contrato' => null,
            'fecha_fin_contrato' => $periodo->fecha_inicio->copy()->addDays(14)->toDateString(),
        ])->save();

        $this->generar($periodo);

        $fila = collect($this->actingAs($this->admin())
            ->getJson("/planillas/periodos/{$periodo->id}/detalle")
            ->assertOk()
            ->json('detalles'))
            ->firstWhere('employee_id', $empleado->id);

        $this->assertNotNull($fila);
        $this->assertSame(15, $fila['dias_pagados']);
    }

    // ========== HELPERS ==========

    private function generar(PlanillaPeriodo $periodo): void
    {
        $this->actingAs($this->admin())
            ->postJson("/planillas/periodos/{$periodo->id}/generar")
            ->assertOk();
    }

    /** Periodo BORRADOR en un mes sin planilla (evita julio/diciembre por gratificaciones). */
    private function periodoLibre(): PlanillaPeriodo
    {
        foreach (range(2030, 2040) as $anio) {
            foreach ([3, 4, 5, 9, 10, 11] as $mes) {
                if (PlanillaPeriodo::where('anio', $anio)->where('mes', $mes)->exists()) {
                    continue;
                }

                return PlanillaPeriodo::create([
                    'anio' => $anio,
                    'mes' => $mes,
                    'fecha_inicio' => sprintf('%04d-%02d-01', $anio, $mes),
                    'fecha_fin' => date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $anio, $mes))),
                    'estado' => 'BORRADOR',
                ]);
            }
        }

        $this->fail('No hay meses libres para crear un periodo de prueba');
    }

    private function empleadoCas(PlanillaPeriodo $periodo): Employee
    {
        $cierre = $periodo->fechaCierre();

        return Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', fn ($query) => $query->whereRaw('UPPER(nombre) = ?', ['CAS']))
            ->whereRaw('COALESCE(fecha_inicio_contrato, fecha_ingreso) <= ?', [$periodo->fecha_fin])
            ->where(function ($query) use ($periodo) {
                $query->whereNull('fecha_fin_contrato')
                    ->orWhere('fecha_fin_contrato', '>=', $periodo->fecha_inicio);
            })
            ->whereHas('remunerations', function ($query) use ($cierre) {
                $query->where('monto', '>', 0)
                    ->where('desde', '<=', $cierre)
                    ->where(function ($sub) use ($cierre) {
                        $sub->whereNull('hasta')->orWhere('hasta', '>=', $cierre);
                    });
            })
            ->firstOrFail();
    }

    /** Básica vigente al cierre del periodo (misma regla que el generador). */
    private function baseVigente(Employee $empleado, PlanillaPeriodo $periodo): float
    {
        $cierre = $periodo->fechaCierre();

        $vigente = EmployeeRemuneration::where('employee_id', $empleado->id)
            ->where('desde', '<=', $cierre)
            ->where(function ($query) use ($cierre) {
                $query->whereNull('hasta')->orWhere('hasta', '>=', $cierre);
            })
            ->orderByDesc('desde')
            ->firstOrFail();

        return round((float) $vigente->monto, 2);
    }

    private function montoConcepto(PlanillaDetalle $detalle, string $codigo): float
    {
        $item = $detalle->items()
            ->whereHas('concepto', fn ($query) => $query->where('codigo', $codigo))
            ->firstOrFail();

        return (float) $item->monto;
    }

    /** Fórmula del Excel: monto − (monto/30 redondeado × días no pagados). */
    private function proporcional(float $monto, int $diasPagados): float
    {
        return round($monto - (round($monto / 30, 2) * (30 - $diasPagados)), 2);
    }

    private function admin()
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
