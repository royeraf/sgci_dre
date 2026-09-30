<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaDetalleItem;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaTardanza;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La línea «Faltas / Tardanzas» de la columna de descuentos del Excel se
 * emite **siempre**, con 0.00 cuando el empleado no tiene faltas (columna
 * «Falt/Tard.»), y con el total de `planilla_tardanzas` no justificadas
 * cuando sí tiene. Vive en PlanillaGenerador::registroTardanzas().
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaFaltasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_empleado_sin_faltas_muestra_la_fila_en_cero(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        $detalle = $this->generarYDetallar($periodo, $empleado);
        $item = $this->itemFaltas($detalle);

        $this->assertNotNull($item, 'La fila de faltas debe existir aunque no haya faltas');
        $this->assertSame('DESCUENTO', $item->tipo);
        $this->assertSame('Faltas / Tardanzas', $item->descripcion);
        $this->assertSame(0.0, (float) $item->monto);
        // N = E − L con L = 0: la base guardada es el total de ingresos
        $this->assertEqualsWithDelta((float) $detalle->total_ingresos, (float) $item->base_calculo, 0.001);
        // El 0.00 no mueve los totales
        $this->assertEqualsWithDelta(
            (float) $detalle->total_ingresos - (float) $detalle->total_descuentos,
            (float) $detalle->neto_pagar,
            0.001
        );
    }

    public function test_empleado_con_faltas_descuenta_el_total_no_justificado(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        PlanillaTardanza::create([
            'periodo_id' => $periodo->id,
            'employee_id' => $empleado->id,
            'fecha' => $periodo->fecha_inicio,
            'dias' => 1,
            'minutos' => 0,
            'total' => 100,
            'justificado' => false,
        ]);

        $detalle = $this->generarYDetallar($periodo, $empleado);
        $item = $this->itemFaltas($detalle);

        $this->assertNotNull($item);
        $this->assertSame(100.0, (float) $item->monto);
        $this->assertEqualsWithDelta(
            (float) $detalle->total_ingresos - 100.0,
            (float) $item->base_calculo,
            0.001
        );
    }

    public function test_las_faltas_justificadas_no_generan_descuento(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        PlanillaTardanza::create([
            'periodo_id' => $periodo->id,
            'employee_id' => $empleado->id,
            'fecha' => $periodo->fecha_inicio,
            'dias' => 1,
            'minutos' => 0,
            'total' => 100,
            'justificado' => true,
        ]);

        $detalle = $this->generarYDetallar($periodo, $empleado);

        $this->assertSame(0.0, (float) $this->itemFaltas($detalle)->monto);
    }

    // ========== HELPERS ==========

    private function generarYDetallar(PlanillaPeriodo $periodo, Employee $empleado): PlanillaDetalle
    {
        $this->actingAs($this->admin())
            ->postJson("/planillas/periodos/{$periodo->id}/generar")
            ->assertOk();

        return PlanillaDetalle::where('periodo_id', $periodo->id)
            ->where('employee_id', $empleado->id)
            ->firstOrFail();
    }

    private function itemFaltas(PlanillaDetalle $detalle): ?PlanillaDetalleItem
    {
        return $detalle->items()
            ->whereHas('concepto', fn ($query) => $query->where('codigo', 'FALTAS_TARDANZAS'))
            ->first();
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
