<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeRemuneration;
use App\Models\License;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Licencias por empleado (`licenses`). Solo descuentan de la planilla las
 * que son `sin_goce` y están APROBADAS: el generador resta esos días en
 * PlanillaGenerador::diasPagados(); si cubren todo lo pagable, el empleado
 * se excluye del periodo (igual que en el Excel, que lo muestra en 0 con la
 * nota «Licencia S/Goce»). Corre contra la BD dev en transacción reversible.
 */
class PlanillaLicenciasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_licencia_sin_goce_que_cubre_el_periodo_excluye_al_empleado(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $this->crearLicencia($empleado, [
            'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
            'fecha_fin' => $periodo->fecha_fin->toDateString(),
            'sin_goce' => true,
        ]);

        $this->generar($periodo);

        $detalle = PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->first();

        $this->assertNull($detalle, 'El empleado con licencia sin goce de todo el periodo no debe figurar en la planilla');
    }

    public function test_licencia_sin_goce_parcial_descuenta_los_dias_del_periodo(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);
        // Del día 15 hasta el cierre: en un mes de 31 días son 17 días sin goce
        $diasSinGoce = (int) $periodo->fecha_inicio->copy()->addDays(14)->diffInDays($periodo->fecha_fin) + 1;

        $this->crearLicencia($empleado, [
            'fecha_inicio' => $periodo->fecha_inicio->addDays(14)->toDateString(),
            'fecha_fin' => $periodo->fecha_fin->toDateString(),
            'sin_goce' => true,
        ]);

        $detalle = $this->generarDetallado($periodo, $empleado);

        $this->assertSame(30 - $diasSinGoce, (int) $detalle->dias_pagados);

        $base = $this->remuneracionBase($empleado, $periodo);
        $esperado = round($base, 2) - round($base / 30, 2) * $diasSinGoce;
        $dl1057 = $detalle->items()
            ->whereHas('concepto', fn ($query) => $query->where('codigo', 'REM_DL1057'))
            ->firstOrFail();

        $this->assertEqualsWithDelta($esperado, (float) $dl1057->monto, 0.01);
    }

    public function test_licencia_con_goce_no_afecta_la_planilla(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $this->crearLicencia($empleado, [
            'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
            'fecha_fin' => $periodo->fecha_fin->toDateString(),
            'sin_goce' => false,
        ]);

        $detalle = $this->generarDetallado($periodo, $empleado);

        $this->assertSame(30, (int) $detalle->dias_pagados);
    }

    public function test_licencia_pendiente_o_de_otro_mes_no_afecta(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $this->crearLicencia($empleado, [
            'fecha_inicio' => $periodo->fecha_inicio->toDateString(),
            'fecha_fin' => $periodo->fecha_fin->toDateString(),
            'sin_goce' => true,
            'estado' => 'PENDIENTE',
        ]);
        $this->crearLicencia($empleado, [
            'fecha_inicio' => $periodo->fecha_inicio->subMonths(2)->toDateString(),
            'fecha_fin' => $periodo->fecha_inicio->subMonth()->toDateString(),
            'sin_goce' => true,
            'estado' => 'APROBADO',
        ]);

        $detalle = $this->generarDetallado($periodo, $empleado);

        $this->assertSame(30, (int) $detalle->dias_pagados);
    }

    public function test_api_licencias_registra_solo_cuenta_dias_con_goce(): void
    {
        $admin = $this->admin();
        $empleado = $this->empleadoCas($this->periodoLibre());
        $usados = (int) $empleado->fresh()->licencias_usadas;

        // Sin goce: no consume días de licencia personal (20 anuales)
        $this->actingAs($admin)->postJson('/planillas/licencias', [
            'employee_id' => $empleado->id,
            'tipo_licencia' => 'Otros',
            'fecha_inicio' => '2031-01-10',
            'fecha_fin' => '2031-07-31',
            'sin_goce' => true,
            'motivo' => 'RDR 1693-2026',
            'estado' => 'APROBADO',
        ])->assertCreated();

        $this->assertSame($usados, (int) $empleado->fresh()->licencias_usadas, 'La licencia sin goce no consume el contador');

        // Con goce: incrementa el contador y se revierte al eliminar
        $respuesta = $this->actingAs($admin)->postJson('/planillas/licencias', [
            'employee_id' => $empleado->id,
            'tipo_licencia' => 'Enfermedad',
            'fecha_inicio' => '2032-03-01',
            'fecha_fin' => '2032-03-05',
            'sin_goce' => false,
            'estado' => 'APROBADO',
        ])->assertCreated();

        $this->assertSame($usados + 5, (int) $empleado->fresh()->licencias_usadas);

        $this->actingAs($admin)
            ->deleteJson("/planillas/licencias/{$respuesta->json('licencia.id')}")
            ->assertOk();

        $this->assertSame($usados, (int) $empleado->fresh()->licencias_usadas);
    }

    public function test_api_licencia_con_goce_sin_dias_disponibles_es_rechazada(): void
    {
        $admin = $this->admin();
        $empleado = $this->empleadoCas($this->periodoLibre());

        $this->actingAs($admin)->postJson('/planillas/licencias', [
            'employee_id' => $empleado->id,
            'tipo_licencia' => 'Personal',
            'fecha_inicio' => '2033-01-01',
            'fecha_fin' => '2033-12-31',
            'sin_goce' => false,
            'estado' => 'APROBADO',
        ])->assertStatus(422);
    }

    // ========== HELPERS ==========

    private function generar(PlanillaPeriodo $periodo): void
    {
        $this->actingAs($this->admin())
            ->postJson("/planillas/periodos/{$periodo->id}/generar")
            ->assertOk();
    }

    private function generarDetallado(PlanillaPeriodo $periodo, Employee $empleado): PlanillaDetalle
    {
        $this->generar($periodo);

        return PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->firstOrFail();
    }

    private function crearLicencia(Employee $empleado, array $atributos): License
    {
        return License::create(array_merge([
            'employee_id' => $empleado->id,
            'dni' => $empleado->dni,
            'tipo_licencia' => 'Otros',
            'motivo' => 'RDR 1693-2026',
            'dias_solicitados' => 1,
            'estado' => 'APROBADO',
            'created_by' => 'test',
        ], $atributos));
    }

    private function remuneracionBase(Employee $empleado, PlanillaPeriodo $periodo): float
    {
        $cierre = $periodo->fechaCierre();

        return (float) EmployeeRemuneration::where('employee_id', $empleado->id)
            ->where('desde', '<=', $cierre)
            ->where(function ($query) use ($cierre) {
                $query->whereNull('hasta')->orWhere('hasta', '>=', $cierre);
            })
            ->orderByDesc('desde')
            ->firstOrFail()
            ->monto;
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

    private function admin()
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
