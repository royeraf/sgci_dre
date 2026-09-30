<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Notas/anotaciones por empleado en el Detalle de Planilla
 * (GET/POST/PUT/DELETE /planillas/notas).
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaNotasTest extends TestCase
{
    use DatabaseTransactions;

    public function test_crear_nota_la_registra_con_su_autor(): void
    {
        $empleado = $this->empleado();

        $respuesta = $this->actingAs($this->admin())
            ->postJson('/planillas/notas', [
                'employee_id' => $empleado->id,
                'texto' => '  Pendiente actualizar la remuneración básica.  ',
            ]);

        $respuesta->assertCreated();
        $this->assertStringContainsString('registrada', $respuesta->json('message'));

        $nota = EmployeeNote::find($respuesta->json('nota.id'));
        $this->assertNotNull($nota);
        $this->assertSame($empleado->id, $nota->employee_id);
        $this->assertSame('Pendiente actualizar la remuneración básica.', $nota->texto);
        $this->assertEquals($this->admin()->id, $nota->registrado_por);
    }

    public function test_listar_notas_devuelve_las_del_empleado_en_orden_desc(): void
    {
        $empleado = $this->empleado();
        $otro = Employee::where('id', '!=', $empleado->id)->firstOrFail();

        EmployeeNote::forceCreate([
            'employee_id' => $empleado->id,
            'texto' => 'Nota antigua',
            'created_at' => '2026-01-01 10:00:00',
            'updated_at' => '2026-01-01 10:00:00',
        ]);
        EmployeeNote::forceCreate([
            'employee_id' => $empleado->id,
            'texto' => 'Nota reciente',
            'created_at' => '2026-02-01 10:00:00',
            'updated_at' => '2026-02-01 10:00:00',
        ]);
        EmployeeNote::create(['employee_id' => $otro->id, 'texto' => 'No debe aparecer']);

        $respuesta = $this->actingAs($this->admin())
            ->getJson('/planillas/notas?employee_id='.$empleado->id);

        $respuesta->assertOk();

        $notas = $respuesta->json('notas');
        $this->assertCount(2, $notas);
        $this->assertSame('Nota reciente', $notas[0]['texto']);
        $this->assertSame('Nota antigua', $notas[1]['texto']);
        $this->assertArrayHasKey('autor', $notas[0]);
        $this->assertArrayHasKey('fecha', $notas[0]);
        $this->assertArrayHasKey('hora', $notas[0]);
        $this->assertArrayHasKey('editado', $notas[0]);
        $this->assertFalse($notas[0]['editado']);
    }

    public function test_actualizar_nota_cambia_el_texto(): void
    {
        $nota = EmployeeNote::create([
            'employee_id' => $this->empleado()->id,
            'texto' => 'Texto original',
        ]);

        $respuesta = $this->actingAs($this->admin())
            ->putJson('/planillas/notas/'.$nota->id, ['texto' => 'Texto corregido']);

        $respuesta->assertOk();
        $this->assertSame('Texto corregido', $nota->fresh()->texto);
    }

    public function test_eliminar_nota_la_quita_de_la_base(): void
    {
        $nota = EmployeeNote::create([
            'employee_id' => $this->empleado()->id,
            'texto' => 'Se borrará',
        ]);

        $this->actingAs($this->admin())
            ->deleteJson('/planillas/notas/'.$nota->id)
            ->assertOk();

        $this->assertNull(EmployeeNote::find($nota->id));
    }

    public function test_crear_nota_sin_texto_es_rechazado(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/planillas/notas', [
                'employee_id' => $this->empleado()->id,
                'texto' => '   ',
            ])
            ->assertStatus(422);
    }

    public function test_crear_nota_con_texto_largo_es_rechazado(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/planillas/notas', [
                'employee_id' => $this->empleado()->id,
                'texto' => str_repeat('a', 1001),
            ])
            ->assertStatus(422);
    }

    public function test_notas_de_empleado_inexistente_son_rechazadas(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/planillas/notas', [
                'employee_id' => 'no-existe',
                'texto' => 'Cualquier cosa',
            ])
            ->assertStatus(422);
    }

    public function test_notas_inexistentes_devuelven_404(): void
    {
        $inexistente = '00000000-0000-0000-0000-000000000000';

        $this->actingAs($this->admin())
            ->putJson('/planillas/notas/'.$inexistente, ['texto' => 'x'])
            ->assertStatus(404);

        $this->actingAs($this->admin())
            ->deleteJson('/planillas/notas/'.$inexistente)
            ->assertStatus(404);
    }

    public function test_las_rutas_de_notas_requieren_sesion(): void
    {
        $this->getJson('/planillas/notas?employee_id=x')
            ->assertStatus(401);
    }

    private function empleado(): Employee
    {
        return Employee::where('estado', 'ACTIVO')->firstOrFail();
    }

    private function admin()
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
