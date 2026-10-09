<?php

namespace Tests\Feature;

use App\Models\PlanillaPeriodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Transiciones de estado del periodo de planilla: solo hay avance
 * (CALCULADA → APROBADA → PAGADA → CERRADA). Aprobar es lo que publica las
 * boletas en el portal público por DNI.
 *
 * La suite corre contra la base de datos de desarrollo (phpunit.xml no fuerza
 * sqlite), así que cada test se envuelve en una transacción que se revierte.
 */
class PlanillaPeriodoEstadoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_aprueba_una_planilla_calculada_y_la_deja_solo_lectura(): void
    {
        $periodo = $this->periodo('CALCULADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'APROBADA'])
            ->assertOk()
            ->assertJsonPath('periodo.estado', 'APROBADA')
            ->assertJsonPath('periodo.editable', false);

        $this->assertSame('APROBADA', $periodo->refresh()->estado);
        $this->assertFalse($periodo->editable);
    }

    public function test_no_aprueba_una_planilla_aun_en_borrador(): void
    {
        $periodo = $this->periodo('BORRADOR');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'APROBADA'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede pasar de BORRADOR a APROBADA');

        $this->assertSame('BORRADOR', $periodo->refresh()->estado);
    }

    public function test_avanza_de_aprobada_a_pagada_y_de_pagada_a_cerrada(): void
    {
        $periodo = $this->periodo('APROBADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'PAGADA'])
            ->assertOk()
            ->assertJsonPath('periodo.estado', 'PAGADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'CERRADA'])
            ->assertOk()
            ->assertJsonPath('periodo.estado', 'CERRADA');

        $this->assertSame('CERRADA', $periodo->refresh()->estado);
    }

    public function test_cierra_directamente_una_planilla_aprobada(): void
    {
        $periodo = $this->periodo('APROBADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'CERRADA'])
            ->assertOk()
            ->assertJsonPath('periodo.estado', 'CERRADA');
    }

    public function test_no_retrocede_de_cerrada_a_aprobada(): void
    {
        $periodo = $this->periodo('CERRADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'APROBADA'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede pasar de CERRADA a APROBADA');

        $this->assertSame('CERRADA', $periodo->refresh()->estado);
    }

    public function test_no_repite_el_estado_actual(): void
    {
        $periodo = $this->periodo('APROBADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'APROBADA'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'La planilla ya está en estado APROBADA');
    }

    public function test_rechaza_estados_invalidos(): void
    {
        $periodo = $this->periodo('CALCULADA');

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'BORRADOR'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);

        $this->patchJson("/planillas/periodos/{$periodo->id}/estado", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    public function test_periodo_inexistente_responde_404(): void
    {
        $this->patchJson('/planillas/periodos/no-existe/estado', ['estado' => 'APROBADA'])
            ->assertStatus(404);
    }

    public function test_requiere_sesion_de_usuario(): void
    {
        auth()->logout();

        $periodo = $this->periodo('CALCULADA');

        // Petición web (sin JSON): el middleware auth redirige a /login.
        $this->patch("/planillas/periodos/{$periodo->id}/estado", ['estado' => 'APROBADA'])
            ->assertRedirect('/login');

        $this->assertSame('CALCULADA', $periodo->refresh()->estado);
    }

    private function periodo(string $estado): PlanillaPeriodo
    {
        foreach (range(2060, 2069) as $anio) {
            foreach (range(1, 12) as $mes) {
                if (PlanillaPeriodo::where('anio', $anio)->where('mes', $mes)->exists()) {
                    continue;
                }

                return PlanillaPeriodo::create([
                    'anio' => $anio,
                    'mes' => $mes,
                    'fecha_inicio' => sprintf('%04d-%02d-01', $anio, $mes),
                    'fecha_fin' => date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $anio, $mes))),
                    'estado' => $estado,
                ]);
            }
        }

        $this->fail('No hay meses libres para crear un periodo de prueba');
    }

    private function admin(): User
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
