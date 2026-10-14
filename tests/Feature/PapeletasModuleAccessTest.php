<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El módulo "Papeletas de Salida" del sistema principal está deshabilitado por
 * ahora para el rol Empleado Portal (ROL012): sus solicitudes se gestionan desde
 * el Portal de Empleados (/portal/papeletas). Debe redirigirse al dashboard,
 * incluso si el módulo fue asignado explícitamente vía modulos_json.
 */
class PapeletasModuleAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function usuario(string $rol, array $overrides = []): User
    {
        return User::create(array_merge([
            'username'  => 'user_' . uniqid(),
            'password'  => Hash::make('clave123'),
            'rol_id'    => $rol,
            'is_active' => true,
        ], $overrides));
    }

    public function test_empleado_portal_es_redirigido_desde_papeletas(): void
    {
        $this->actingAs($this->usuario('ROL012'))
            ->get('/papeletas')
            ->assertRedirect('/dashboard');
    }

    public function test_empleado_portal_con_modulo_asignado_tambien_es_bloqueado(): void
    {
        $this->actingAs($this->usuario('ROL012', ['modulos_json' => ['dashboard', 'papeletas']]))
            ->get('/papeletas')
            ->assertRedirect('/dashboard');
    }

    public function test_administrador_sigue_accediendo_a_papeletas(): void
    {
        $this->actingAs($this->usuario('ROL001'))
            ->get('/papeletas')
            ->assertOk();
    }
}
