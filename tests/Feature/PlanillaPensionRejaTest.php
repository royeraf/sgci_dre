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
 * Empleados que NO están sujetos a descuentos AFP/ONP:
 *  - régimen pensionario con es_reja = true (REJA), y
 *  - empleados sin perfil / sin régimen asignado.
 * La regla vive en PlanillaGenerador::retencionesPension() (return [] en ambos
 * casos) y se comunica en el modal de perfil de planilla.
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaPensionRejaTest extends TestCase
{
    private const CONCEPTOS_PENSION = ['AFP_FONDO', 'AFP_SEGURO', 'AFP_COMISION', 'ONP_19990'];

    use DatabaseTransactions;

    public function test_empleado_con_regimen_reja_no_tiene_descuentos_afp_ni_onp(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);
        $reja = PlanillaRegimenPensionario::where('es_reja', true)->firstOrFail();

        EmployeePayrollProfile::where('employee_id', $empleado->id)->delete();
        EmployeePayrollProfile::create([
            'employee_id' => $empleado->id,
            'regimen_pensionario_id' => $reja->id,
        ]);

        $detalle = $this->generarYDetallar($periodo, $empleado);

        $this->assertSame([], $this->itemsDe($detalle, self::CONCEPTOS_PENSION));
        $this->assertContains('ESSALUD', $this->codigosItems($detalle));
    }

    public function test_empleado_sin_regimen_pensionario_no_tiene_descuentos_afp_ni_onp(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);

        EmployeePayrollProfile::where('employee_id', $empleado->id)->delete();

        $detalle = $this->generarYDetallar($periodo, $empleado);

        $this->assertSame([], $this->itemsDe($detalle, self::CONCEPTOS_PENSION));
        $this->assertContains('ESSALUD', $this->codigosItems($detalle));
    }

    public function test_empleado_con_regimen_normal_si_tiene_descuento(): void
    {
        $periodo = $this->periodoLibre();
        $empleado = $this->empleadoCas($periodo);
        $normal = PlanillaRegimenPensionario::where('es_reja', false)
            ->where('activo', true)
            ->firstOrFail();

        EmployeePayrollProfile::where('employee_id', $empleado->id)->delete();
        EmployeePayrollProfile::create([
            'employee_id' => $empleado->id,
            'regimen_pensionario_id' => $normal->id,
            'tipo_comision' => 'SALDO',
        ]);

        $detalle = $this->generarYDetallar($periodo, $empleado);

        $esperado = $normal->tipo === 'ONP' ? 'ONP_19990' : 'AFP_FONDO';
        $this->assertContains($esperado, $this->codigosItems($detalle));
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

    /** @return array<int, string> */
    private function codigosItems(PlanillaDetalle $detalle): array
    {
        return $detalle->items()->with('concepto')->get()
            ->map(fn ($item) => $item->concepto?->codigo)
            ->filter()
            ->values()
            ->all();
    }

    /** @param array<int, string> $codigos @return array<int, string> */
    private function itemsDe(PlanillaDetalle $detalle, array $codigos): array
    {
        return array_values(array_intersect($codigos, $this->codigosItems($detalle)));
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
