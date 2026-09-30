<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaConceptoAsignacion;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Badge del icono «Asignar conceptos»: el detalle del periodo y el listado de
 * remuneraciones exponen asignaciones_count para marcar al empleado que tiene
 * conceptos personalizados.
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaAsignacionesBadgeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_el_detalle_expone_asignaciones_count(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $this->borrarAsignaciones($empleado);
        PlanillaConceptoAsignacion::create([
            'concepto_id' => PlanillaConcepto::where('codigo', 'REINTEGRO')->firstOrFail()->id,
            'employee_id' => $empleado->id,
            'monto' => 88.31,
            'activo' => true,
        ]);

        $this->generar($periodo);

        $filas = collect($this->actingAs($this->admin())
            ->getJson("/planillas/periodos/{$periodo->id}/detalle")
            ->assertOk()
            ->json('detalles'));

        $fila = $filas->firstWhere('employee_id', $empleado->id);

        $this->assertNotNull($fila, 'El empleado no figura en el detalle');
        $this->assertSame(1, $fila['asignaciones_count']);
        $this->assertContains(0, $filas->pluck('asignaciones_count')->all());
    }

    public function test_el_listado_de_remuneraciones_expone_asignaciones_count(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $this->borrarAsignaciones($empleado);
        PlanillaConceptoAsignacion::create([
            'concepto_id' => PlanillaConcepto::where('codigo', 'REINTEGRO')->firstOrFail()->id,
            'employee_id' => $empleado->id,
            'monto' => 88.31,
            'activo' => false,
        ]);

        $fila = collect($this->actingAs($this->admin())
            ->getJson('/planillas/remuneraciones')
            ->assertOk()
            ->json())
            ->firstWhere('id', $empleado->id);

        $this->assertNotNull($fila, 'El empleado no figura en el listado de remuneraciones');
        $this->assertSame(1, $fila['asignaciones_count']);
    }

    // ========== HELPERS ==========

    private function generar(PlanillaPeriodo $periodo): void
    {
        $this->actingAs($this->admin())
            ->postJson("/planillas/periodos/{$periodo->id}/generar")
            ->assertOk();
    }

    private function borrarAsignaciones(Employee $empleado): void
    {
        PlanillaConceptoAsignacion::where('employee_id', $empleado->id)->delete();
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
