<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeePayrollProfile;
use App\Models\EmployeeRemuneration;
use App\Models\HRContractType;
use App\Models\HRPosition;
use App\Models\Person;
use App\Models\PlanillaBanco;
use App\Models\PlanillaRegimenPensionario;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Alta de empleados CAS desde Remuneraciones (POST /planillas/empleados).
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaEmpleadoAltaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_alta_completa_crea_empleado_remuneracion_y_perfil(): void
    {
        $dni = $this->dniLibre();

        $respuesta = $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', $this->payloadCompleto($dni));

        $respuesta->assertCreated();
        $this->assertStringContainsString('registrado', $respuesta->json('message'));

        $employee = Employee::find($respuesta->json('employee_id'));
        $this->assertNotNull($employee);
        $this->assertSame('ACTIVO', $employee->estado);
        $this->assertSame('CAS', strtoupper($employee->contractType->nombre));
        $this->assertSame('TRANSITORIO', $employee->modalidad_cas);
        $this->assertSame('2026-12-31', $employee->fecha_fin_contrato?->format('Y-m-d'));

        $remuneracion = EmployeeRemuneration::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEqualsWithDelta(2500, (float) $remuneracion->monto, 0.01);
        $this->assertNull($remuneracion->hasta);
        $this->assertSame('2026-09-01', $remuneracion->desde->format('Y-m-d'));

        $perfil = EmployeePayrollProfile::where('employee_id', $employee->id)->firstOrFail();
        $this->assertNotNull($perfil->regimen_pensionario_id);
        $this->assertNotNull($perfil->banco_id);

        $fila = collect($this->actingAs($this->admin())->getJson('/planillas/remuneraciones')->json())
            ->firstWhere('dni', $dni);

        $this->assertNotNull($fila, 'El empleado debe aparecer en la tabla de Remuneraciones');
        $this->assertEqualsWithDelta(2500, (float) $fila['remuneracion_base'], 0.01);
        $this->assertSame('TRANSITORIO', $fila['modalidad_cas']);
    }

    public function test_alta_minima_sin_pension_ni_banco_es_aceptada(): void
    {
        $dni = $this->dniLibre();

        $payload = $this->payloadCompleto($dni);
        unset(
            $payload['regimen_pensionario_id'],
            $payload['cuspp'],
            $payload['tipo_comision'],
            $payload['banco_id'],
            $payload['cuenta_ahorro'],
        );

        $respuesta = $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', $payload);

        $respuesta->assertCreated();

        $employee = Employee::find($respuesta->json('employee_id'));
        $this->assertNull($employee->payrollProfile);
        $this->assertNotNull($employee->remunerations()->first());
    }

    public function test_alta_rechaza_dni_ya_registrado(): void
    {
        $dni = $this->dniLibre();

        $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', $this->payloadCompleto($dni))
            ->assertCreated();

        $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', $this->payloadCompleto($dni))
            ->assertStatus(422)
            ->assertJson(['message' => 'Esta persona ya está registrada como empleado.']);
    }

    public function test_alta_rechaza_base_invalida_y_modalidad_invalida(): void
    {
        $base = $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', array_merge($this->payloadCompleto($this->dniLibre()), ['remuneracion' => 0]));
        $base->assertStatus(422);
        $this->assertArrayHasKey('remuneracion', $base->json('errors'));

        $modalidad = $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', array_merge($this->payloadCompleto($this->dniLibre()), ['modalidad_cas' => 'EVENTUAL']));
        $modalidad->assertStatus(422);
        $this->assertArrayHasKey('modalidad_cas', $modalidad->json('errors'));
    }

    public function test_alta_sin_contrato_cas_rechazada(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', array_merge(
                $this->payloadCompleto($this->dniLibre()),
                ['contract_type_id' => 'no-existe'],
            ));

        $respuesta->assertStatus(422);
        $this->assertArrayHasKey('contract_type_id', $respuesta->json('errors'));
    }

    public function test_catalogos_empleados_disponibles_en_planillas(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/catalogos-empleados')
            ->assertOk()
            ->assertJsonStructure([
                'cargos',
                'direcciones',
                'oficinas',
                'tipos_contrato',
            ]);

        $tipos = $this->actingAs($this->admin())->getJson('/planillas/catalogos-empleados')->json('tipos_contrato');
        $this->assertNotEmpty(collect($tipos)->firstWhere('nombre', 'CAS'));
    }

    public function test_consulta_dni_detecta_empleado_ya_registrado(): void
    {
        $dni = $this->dniLibre();

        $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', $this->payloadCompleto($dni))
            ->assertCreated();

        $respuesta = $this->actingAs($this->admin())
            ->getJson('/planillas/consultar-dni?dni=' . $dni);

        $respuesta->assertOk();
        $this->assertTrue($respuesta->json('registrado'));
        $this->assertFalse($respuesta->json('success'));
        $this->assertSame('El DNI ya está registrado como empleado.', $respuesta->json('message'));
        $this->assertSame('ACTIVO', $respuesta->json('empleado.estado'));
        $this->assertNotNull($respuesta->json('empleado.nombre_completo'));
    }

    public function test_consulta_dni_usa_datos_locales_sin_llamar_a_reniec(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $dni = $this->dniLibre();
        Person::create([
            'dni' => $dni,
            'nombres' => 'Local',
            'apellidos' => 'Sin Empleado',
            'tipo' => 'EXTERNO',
            'is_active' => true,
        ]);

        $respuesta = $this->actingAs($this->admin())
            ->getJson('/planillas/consultar-dni?dni=' . $dni);

        $respuesta->assertOk();
        $this->assertTrue($respuesta->json('success'));
        $this->assertFalse($respuesta->json('registrado'));
        $this->assertSame('Local', $respuesta->json('data.nombres'));
        Http::assertNothingSent();
    }

    public function test_consulta_dni_reniec_autocompleta_nombres_y_apellidos(): void
    {
        $dni = $this->dniLibre();

        Http::fake([
            'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
                'error' => false,
                'document_number' => $dni,
                'first_name' => 'MARIA',
                'first_last_name' => 'GARCIA',
                'second_last_name' => 'LOPEZ',
                'full_name' => 'GARCIA LOPEZ MARIA',
            ]),
            '*' => Http::response([], 404),
        ]);

        $respuesta = $this->actingAs($this->admin())
            ->getJson('/planillas/consultar-dni?dni=' . $dni);

        $respuesta->assertOk();
        $this->assertTrue($respuesta->json('success'));
        $this->assertFalse($respuesta->json('registrado'));
        $this->assertSame('MARIA', $respuesta->json('data.nombres'));
        $this->assertSame('GARCIA', $respuesta->json('data.apellido_paterno'));
        $this->assertSame('LOPEZ', $respuesta->json('data.apellido_materno'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.decolecta.com'));
    }

    public function test_consulta_dni_sin_coincidencias_no_bloquea_la_alta_manual(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $dni = $this->dniLibre();

        $respuesta = $this->actingAs($this->admin())
            ->getJson('/planillas/consultar-dni?dni=' . $dni);

        $respuesta->assertOk();
        $this->assertFalse($respuesta->json('success'));
        $this->assertFalse($respuesta->json('registrado'));
        $this->assertNull($respuesta->json('data'));

        $this->actingAs($this->admin())
            ->postJson('/planillas/empleados', $this->payloadCompleto($dni))
            ->assertCreated();
    }

    public function test_consulta_dni_rechaza_formato_invalido(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/consultar-dni?dni=1234567')
            ->assertStatus(422);
    }

    private function payloadCompleto(string $dni): array
    {
        return [
            'dni' => $dni,
            'nombres' => 'Empleado',
            'apellidos' => 'Prueba Alta',
            'cargo_id' => HRPosition::first()?->id,
            'fecha_ingreso' => '2026-09-01',
            'contract_type_id' => HRContractType::where('nombre', 'CAS')->firstOrFail()->id,
            'remuneracion' => 2500,
            'remuneracion_desde' => '2026-09-01',
            'modalidad_cas' => 'TRANSITORIO',
            'fecha_inicio_contrato' => '2026-09-01',
            'fecha_fin_contrato' => '2026-12-31',
            'regimen_pensionario_id' => PlanillaRegimenPensionario::firstOrFail()->id,
            'cuspp' => 'ABCDE12345',
            'tipo_comision' => 'FLUJO',
            'banco_id' => PlanillaBanco::firstOrFail()->id,
            'cuenta_ahorro' => '1234567890',
        ];
    }

    private function dniLibre(): string
    {
        do {
            $dni = (string) random_int(10000000, 99999999);
        } while (Person::where('dni', $dni)->exists());

        return $dni;
    }

    private function admin()
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
