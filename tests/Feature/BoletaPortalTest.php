<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Portal público de boletas de pago: consulta por DNI sin cuenta, descarga
 * del PDF y confirmación de revisión (badge "Revisada" en el módulo admin).
 *
 * La suite corre contra la base de datos de desarrollo (phpunit.xml no fuerza
 * sqlite), así que cada test se envuelve en una transacción que se revierte.
 */
class BoletaPortalTest extends TestCase
{
    use DatabaseTransactions;

    /** Respuesta uniforme: no distingue DNI inexistente, inactivo o sin boletas. */
    private const MENSAJE_404 = 'No se encontraron boletas para el DNI indicado. Verifique el número o intente más tarde.';

    private PlanillaPeriodo $periodo;

    private PlanillaDetalle $detalle;

    private PlanillaDetalle $ajeno;

    private Employee $empleado;

    private Employee $otro;

    private string $dni;

    protected function setUp(): void
    {
        parent::setUp();

        // La página del portal es Inertia con @vite en el root view.
        $this->withoutVite();

        $this->empleado = $this->empleadoActivo(null);
        $this->otro = $this->empleadoActivo($this->empleado->id);

        $this->dni = $this->dniLibre();
        $this->empleado->person->update(['dni' => $this->dni]);

        $this->periodo = $this->periodoLibre('APROBADA');
        $this->detalle = $this->crearDetalle($this->empleado, $this->periodo);
        $this->ajeno = $this->crearDetalle($this->otro, $this->periodo);
        $this->crearItems($this->detalle);
    }

    public function test_la_pagina_publica_renderiza_sin_identificacion(): void
    {
        $this->get('/boletas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boletas/Portal')
                ->where('empleado', null)
                ->has('boletas', 0));
    }

    public function test_consultar_identifica_y_devuelve_solo_sus_boletas_visibles(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])
            ->assertOk()
            ->assertJsonPath('empleado.dni', $this->dni)
            ->assertJsonPath('empleado.apellidos_nombres', $this->empleado->nombre_completo)
            ->assertJsonCount(1, 'boletas')
            ->assertJsonPath('boletas.0.detalle_id', $this->detalle->id)
            ->assertJsonPath('boletas.0.periodo.estado', 'APROBADA')
            ->assertJsonPath('boletas.0.revisada_en', null);

        $this->assertSame($this->empleado->id, session('boleta_portal_employee_id'));
    }

    public function test_consultar_con_dni_inexistente_responde_404_con_mensaje_uniforme(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dniLibre()])
            ->assertStatus(404)
            ->assertJsonPath('message', self::MENSAJE_404);

        $this->assertNull(session('boleta_portal_employee_id'));
    }

    public function test_consultar_con_dni_de_empleado_inactivo_responde_404(): void
    {
        $this->empleado->update(['estado' => 'INACTIVO']);

        $this->postJson('/boletas/consultar', ['dni' => $this->dni])
            ->assertStatus(404)
            ->assertJsonPath('message', self::MENSAJE_404);

        $this->assertNull(session('boleta_portal_employee_id'));
    }

    public function test_solo_los_periodos_aprobados_pagados_o_cerrados_son_consultables(): void
    {
        foreach (['PAGADA', 'CERRADA'] as $estado) {
            $this->periodo->update(['estado' => $estado]);

            $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();
        }

        foreach (['BORRADOR', 'CALCULADA'] as $estado) {
            $this->periodo->update(['estado' => $estado]);

            $this->postJson('/boletas/consultar', ['dni' => $this->dni])
                ->assertStatus(404)
                ->assertJsonPath('message', self::MENSAJE_404);
        }
    }

    public function test_consultar_valida_que_el_dni_tenga_8_digitos(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => '12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dni']);

        $this->postJson('/boletas/consultar', ['dni' => 'abcdefgh'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dni']);
    }

    public function test_las_consultas_de_un_mismo_dni_estan_limitadas(): void
    {
        $dni = $this->dniLibre();

        foreach (range(1, 10) as $intento) {
            $this->postJson('/boletas/consultar', ['dni' => $dni])->assertStatus(404);
        }

        $this->postJson('/boletas/consultar', ['dni' => $dni])->assertStatus(429);
    }

    public function test_un_refresco_de_pagina_conserva_la_identificacion(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();

        $this->get('/boletas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boletas/Portal')
                ->where('empleado.dni', $this->dni)
                ->has('boletas', 1)
                ->where('boletas.0.detalle_id', $this->detalle->id));
    }

    public function test_si_dejan_de_haber_boletas_visibles_se_olvida_la_identificacion(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();

        $this->periodo->update(['estado' => 'BORRADOR']);

        $this->get('/boletas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boletas/Portal')
                ->where('empleado', null)
                ->has('boletas', 0));

        $this->assertNull(session('boleta_portal_employee_id'));
    }

    public function test_salir_cierra_la_identificacion(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();

        $this->postJson('/boletas/salir')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada');

        $this->getJson("/boletas/{$this->detalle->id}")->assertStatus(404);
    }

    public function test_ver_boleta_propia_ajena_o_sin_sesion_responde_como_corresponde(): void
    {
        // Sin identificación.
        $this->getJson("/boletas/{$this->detalle->id}")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Boleta no disponible. Consulte nuevamente con su DNI.');

        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();

        // Boleta propia: vista previa completa.
        $this->getJson("/boletas/{$this->detalle->id}")
            ->assertOk()
            ->assertJsonPath('trabajador.dni', $this->dni)
            ->assertJsonPath('totales.neto_pagar', 2700);

        // Boleta de otro trabajador del mismo periodo visible.
        $this->getJson("/boletas/{$this->ajeno->id}")->assertStatus(404);
    }

    public function test_pdf_de_boleta_propia_se_descarga(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();

        $response = $this->get("/boletas/{$this->detalle->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_la_pagina_de_verificacion_por_qr_muestra_los_datos(): void
    {
        $codigo = app(\App\Services\Planilla\BoletaService::class)->codigoDeDetalle($this->detalle);

        $this->get("/boletas/verificar/{$this->detalle->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boletas/Verificar')
                ->where('boleta.codigo_boleta', $codigo)
                ->where('boleta.periodo', $this->periodo->nombre_periodo)
                ->where('boleta.apellidos_nombres', $this->empleado->nombre_completo));
    }

    public function test_la_pagina_de_verificacion_no_valida_si_no_existe_o_no_es_visible(): void
    {
        // Identificador inexistente.
        $this->get('/boletas/verificar/aaaaaaaa-bbbb-cccc-dddd-eeeeffff0000')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Boletas/Verificar')
                ->where('boleta', null));

        // Periodo no visible: deja de ser una boleta vigente.
        $this->periodo->update(['estado' => 'CALCULADA']);

        $this->get("/boletas/verificar/{$this->detalle->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('boleta', null));
    }

    public function test_pdf_sin_identificacion_redirige_al_portal_y_no_a_login(): void
    {
        $this->get("/boletas/{$this->detalle->id}/pdf")
            ->assertRedirect(route('boletas.portal'));
    }

    public function test_revisar_requiere_identificacion_es_idempotente_y_se_expone_en_el_listado(): void
    {
        // Sin identificación y con boleta ajena: 404 (nunca 401).
        $this->postJson("/boletas/{$this->detalle->id}/revisar")->assertStatus(404);

        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();

        $this->postJson("/boletas/{$this->ajeno->id}/revisar")->assertStatus(404);

        $this->postJson("/boletas/{$this->detalle->id}/revisar")
            ->assertOk()
            ->assertJsonPath('message', 'Boleta confirmada como revisada');

        $this->assertNotNull($this->detalle->refresh()->revisada_en);

        // Idempotente: conserva la primera fecha de confirmación.
        $anterior = now()->subDays(3)->startOfSecond();
        $this->detalle->update(['revisada_en' => $anterior]);

        $this->postJson("/boletas/{$this->detalle->id}/revisar")->assertOk();

        $this->assertSame(
            $anterior->toDateTimeString(),
            $this->detalle->refresh()->revisada_en->toDateTimeString()
        );

        // El listado del portal expone la confirmación.
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])
            ->assertOk()
            ->assertJsonPath('boletas.0.revisada_en', $anterior->toDateTimeString());
    }

    public function test_al_aprobar_el_periodo_las_boletas_se_publican_en_el_portal(): void
    {
        // Mientras la planilla solo está calculada, el portal no muestra nada.
        $this->periodo->update(['estado' => 'CALCULADA']);

        $this->postJson('/boletas/consultar', ['dni' => $this->dni])
            ->assertStatus(404)
            ->assertJsonPath('message', self::MENSAJE_404);

        // El admin aprueba el periodo: la boleta pasa a estar visible.
        $this->actingAs($this->admin())
            ->patchJson("/planillas/periodos/{$this->periodo->id}/estado", ['estado' => 'APROBADA'])
            ->assertOk()
            ->assertJsonPath('periodo.estado', 'APROBADA');

        $this->postJson('/boletas/consultar', ['dni' => $this->dni])
            ->assertOk()
            ->assertJsonCount(1, 'boletas')
            ->assertJsonPath('boletas.0.detalle_id', $this->detalle->id);
    }

    public function test_el_listado_admin_muestra_la_columna_revisada(): void
    {
        $this->postJson('/boletas/consultar', ['dni' => $this->dni])->assertOk();
        $this->postJson("/boletas/{$this->detalle->id}/revisar")->assertOk();

        $boletas = collect(
            $this->actingAs($this->admin())
                ->getJson("/planillas/boletas/periodo/{$this->periodo->id}")
                ->assertOk()
                ->json('boletas')
        );

        $revisada = $boletas->firstWhere('detalle_id', $this->detalle->id);
        $pendiente = $boletas->firstWhere('detalle_id', $this->ajeno->id);

        $this->assertNotNull($revisada);
        $this->assertNotNull($revisada['revisada_en']);

        $this->assertNotNull($pendiente);
        $this->assertNull($pendiente['revisada_en']);
    }

    /**
     * Empleado CAS ACTIVO con person, sin boletas en periodos visibles (así el
     * listado del portal es predecible aunque la BD de desarrollo tenga datos).
     */
    private function empleadoActivo(?string $exceptoId): Employee
    {
        $conBoletasVisibles = PlanillaDetalle::query()
            ->join('planilla_periodos', 'planilla_periodos.id', '=', 'planilla_detalles.periodo_id')
            ->whereIn('planilla_periodos.estado', PlanillaPeriodo::ESTADOS_BOLETA_VISIBLE)
            ->select('planilla_detalles.employee_id');

        return Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', fn ($query) => $query->whereRaw('UPPER(nombre) = ?', ['CAS']))
            ->whereHas('person')
            ->whereNotIn('id', $conBoletasVisibles)
            ->when($exceptoId, fn ($query) => $query->where('id', '!=', $exceptoId))
            ->inRandomOrder()
            ->firstOrFail();
    }

    /** DNI de 8 dígitos que no esté en people.dni (columna UNIQUE). */
    private function dniLibre(): string
    {
        foreach (range(1, 50) as $intento) {
            $dni = (string) random_int(48000000, 48999999);

            if (!Person::where('dni', $dni)->exists()) {
                return $dni;
            }
        }

        $this->fail('No se encontró un DNI libre para la prueba');
    }

    /** Periodo en un mes sin planilla (evita chocar con el unique (anio, mes)). */
    private function periodoLibre(string $estado): PlanillaPeriodo
    {
        foreach (range(2090, 2099) as $anio) {
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

    private function crearDetalle(Employee $empleado, PlanillaPeriodo $periodo): PlanillaDetalle
    {
        return $periodo->detalles()->create([
            'employee_id' => $empleado->id,
            'remuneracion_base' => 3000,
            'total_ingresos' => 3000,
            'total_descuentos' => 300,
            'total_aportaciones' => 270,
            'neto_pagar' => 2700,
        ]);
    }

    private function crearItems(PlanillaDetalle $detalle): void
    {
        $detalle->items()->createMany([
            ['tipo' => 'INGRESO', 'descripcion' => 'Remuneraciones DL 1057', 'base_calculo' => 3000, 'porcentaje' => null, 'monto' => 3000, 'orden' => 1],
            ['tipo' => 'DESCUENTO', 'descripcion' => 'Ley 19990 (ONP)', 'base_calculo' => 3000, 'porcentaje' => 0.13, 'monto' => 300, 'orden' => 2],
            ['tipo' => 'APORTACION', 'descripcion' => 'Essalud', 'base_calculo' => 3000, 'porcentaje' => 0.09, 'monto' => 270, 'orden' => 3],
        ]);
    }

    private function admin(): User
    {
        return User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
