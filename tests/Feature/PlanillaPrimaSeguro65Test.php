<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeePayrollProfile;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaRegimenPensionario;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Prima de seguro AFP exenta a partir de los 65 años.
 *
 * Regla en PlanillaGenerador::primaSeguroExenta(): a partir del MES SIGUIENTE
 * a aquel en que el empleado cumple 65 años (`people.fecha_nacimiento` + 65),
 * la fila `AFP_SEGURO` se emite en 0.00 (sin tasa ni base, patrón REJA); el
 * mes del cumpleaños aún se cobra completo. Sin fecha de nacimiento se cobra.
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaPrimaSeguro65Test extends TestCase
{
    use DatabaseTransactions;

    public function test_prima_seguro_se_cobra_en_el_mes_en_que_cumple_65(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoAfp($periodo);
        $this->setNacimiento($empleado, sprintf('%04d-%02d-10', $periodo->anio - 65, $periodo->mes));

        $item = $this->itemSeguro($this->generarYDetallar($periodo, $empleado));

        $this->assertNotNull($item, 'En el mes del cumpleaños la prima sigue cobrándose');
        $this->assertGreaterThan(0, (float) $item->monto);
        $this->assertGreaterThan(0, (float) $item->base_calculo);
        $this->assertNotNull($item->porcentaje);
    }

    public function test_prima_seguro_no_se_cobra_desde_el_mes_siguiente_al_cumple_65(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoAfp($periodo);
        $mesCumple = $periodo->mes - 1; // cumple 65 el mes anterior al del periodo
        $this->setNacimiento($empleado, sprintf('%04d-%02d-10', $periodo->anio - 65, $mesCumple));

        $detalle = $this->generarYDetallar($periodo, $empleado);
        $item = $this->itemSeguro($detalle);

        $this->assertNotNull($item, 'La fila Prima de seguro debe seguir presente, pero en 0.00');
        $this->assertSame(0.0, (float) $item->monto);
        $this->assertSame(0.0, (float) $item->base_calculo);
        $this->assertNull($item->porcentaje);
        // El resto de la AFP sigue descontándose
        $this->assertContains('AFP_FONDO', $this->codigosItems($detalle));
        $fondo = $detalle->items()->with('concepto')->get()
            ->first(fn ($i) => $i->concepto?->codigo === 'AFP_FONDO');
        $this->assertGreaterThan(0, (float) $fondo->monto);
        // La fila en cero no altera los totales
        $this->assertEqualsWithDelta(
            (float) $detalle->items()->where('tipo', 'DESCUENTO')->sum('monto'),
            (float) $detalle->total_descuentos,
            0.001
        );
    }

    public function test_menor_de_65_paga_prima_normal(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoAfp($periodo);
        $this->setNacimiento($empleado, sprintf('%04d-01-15', $periodo->anio - 40));

        $item = $this->itemSeguro($this->generarYDetallar($periodo, $empleado));

        $this->assertNotNull($item);
        $this->assertGreaterThan(0, (float) $item->monto);
        $this->assertNotNull($item->porcentaje);
    }

    public function test_empleado_sin_fecha_nacimiento_paga_prima_normal(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoAfp($periodo);
        $empleado->person?->update(['fecha_nacimiento' => null]);

        $item = $this->itemSeguro($this->generarYDetallar($periodo, $empleado));

        $this->assertNotNull($item, 'Sin fecha de nacimiento no se puede eximir');
        $this->assertGreaterThan(0, (float) $item->monto);
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

    private function itemSeguro(PlanillaDetalle $detalle)
    {
        return $detalle->items()->with('concepto')->get()
            ->first(fn ($item) => $item->concepto?->codigo === 'AFP_SEGURO');
    }

    /** @return array<int, string> */
    private function codigosItems(PlanillaDetalle $detalle): array
    {
        return $detalle->items()->with('concepto')->get()
            ->map(fn ($item) => $item->concepto?->codigo)
            ->filter()
            ->values()
            ->all();
    }

    private function setNacimiento(Employee $empleado, string $fecha): void
    {
        $empleado->person?->update(['fecha_nacimiento' => $fecha]);
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

    /** Empleado CAS vigente con perfil sobre un régimen AFP normal (sin REJA). */
    private function empleadoAfp(PlanillaPeriodo $periodo): Employee
    {
        $cierre = $periodo->fechaCierre();

        $empleado = Employee::where('estado', 'ACTIVO')
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

        $afp = PlanillaRegimenPensionario::where('tipo', 'AFP')
            ->where('es_reja', false)
            ->where('activo', true)
            ->firstOrFail();

        EmployeePayrollProfile::where('employee_id', $empleado->id)->delete();
        EmployeePayrollProfile::create([
            'employee_id' => $empleado->id,
            'regimen_pensionario_id' => $afp->id,
            'tipo_comision' => 'SALDO',
        ]);

        return $empleado->fresh(['person', 'payrollProfile.regimenPensionario']);
    }

    private function admin()
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
