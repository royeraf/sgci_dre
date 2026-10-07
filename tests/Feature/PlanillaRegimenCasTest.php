<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\HRContractType;
use App\Models\HRPosition;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El módulo administra el régimen CAS por prefijo de tipo de contrato: en
 * algunas instalaciones el catálogo trae «CAS» y en otras «CAS N.° 1057».
 * Una coincidencia exacta dejaba la tabla de Remuneraciones vacía.
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaRegimenCasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_variantes_del_nombre_cas_aparecen_en_remuneraciones(): void
    {
        $cas = $this->empleadoConTipo('CAS', 'Variante Corta');
        $cas1057 = $this->empleadoConTipo('CAS N.° 1057', 'Variante Mil');
        $nombrado = $this->empleadoConTipo('Nombrado', 'Fuera Del Regimen');

        $filas = collect(
            $this->actingAs($this->admin())->getJson('/planillas/remuneraciones')->json()
        );

        $this->assertNotNull($filas->firstWhere('dni', $cas->dni));
        $this->assertNotNull(
            $filas->firstWhere('dni', $cas1057->dni),
            'El tipo de contrato «CAS N.° 1057» debe reconocerse como régimen CAS'
        );
        $this->assertNull($filas->firstWhere('dni', $nombrado->dni));
    }

    public function test_el_scope_del_regimen_planilla_solo_acepta_prefijo_cas(): void
    {
        $this->empleadoConTipo('CAS', 'Prefijo Exacto');
        $this->empleadoConTipo('CAS N.° 1057', 'Prefijo Numerado');
        $this->empleadoConTipo('Locador', 'Sin Prefijo');

        $dnis = Employee::delRegimenPlanilla()
            ->with('person')
            ->get()
            ->pluck('dni')
            ->all();

        $this->assertContains('77777701', $dnis);
        $this->assertContains('77777702', $dnis);
        $this->assertNotContains('77777704', $dnis);
    }

    /**
     * Crea un empleado ACTIVO con el tipo de contrato indicado. Los DNI son
     * fijos por tipo para que las aserciones sean deterministas.
     */
    private function empleadoConTipo(string $tipoContrato, string $apellido): Employee
    {
        $dnis = [
            'CAS' => '77777701',
            'CAS N.° 1057' => '77777702',
            'Nombrado' => '77777703',
            'Locador' => '77777704',
        ];

        $persona = Person::firstOrNew(['dni' => $dnis[$tipoContrato] ?? random_int(77777000, 77777999)]);
        $persona->nombres = 'Empleado';
        $persona->apellidos = $apellido;
        $persona->tipo = 'INTERNO';
        $persona->is_active = true;
        $persona->save();

        return Employee::updateOrCreate(
            ['person_id' => $persona->id],
            [
                'position_id' => HRPosition::first()?->id,
                'contract_type_id' => HRContractType::firstOrCreate(
                    ['nombre' => $tipoContrato],
                    ['descripcion' => $tipoContrato, 'activo' => true]
                )->id,
                'estado' => 'ACTIVO',
                'fecha_ingreso' => now()->toDateString(),
            ]
        );
    }

    private function admin()
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
