<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Apartado "Mis Boletas" del sistema principal: el trabajador autenticado ve
 * solo sus propias boletas de periodos visibles, descarga su PDF y confirma
 * la revisión, sin permisos del módulo admin de planillas.
 *
 * La suite corre contra la base de datos de desarrollo (phpunit.xml no fuerza
 * sqlite), así que cada test se envuelve en una transacción que se revierte.
 */
class MisBoletasTest extends TestCase
{
    use DatabaseTransactions;

    private PlanillaPeriodo $periodo;

    private PlanillaDetalle $detalle;

    private PlanillaDetalle $ajeno;

    private Employee $empleado;

    private Employee $otro;

    private User $usuario;

    private string $dni;

    protected function setUp(): void
    {
        parent::setUp();

        // La página del apartado es Inertia con @vite en el root view.
        $this->withoutVite();

        $this->empleado = $this->empleadoActivo(null);
        $this->otro = $this->empleadoActivo($this->empleado->id);

        $this->dni = $this->dniLibre();
        $this->empleado->person->update(['dni' => $this->dni]);

        $this->usuario = User::create([
            'person_id' => $this->empleado->person_id,
            'username' => $this->dni,
            'dni' => $this->dni,
            'password' => Hash::make($this->dni),
            'rol_id' => 'ROL012',
            'is_active' => true,
        ]);

        $this->periodo = $this->periodoLibre('APROBADA');
        $this->detalle = $this->crearDetalle($this->empleado, $this->periodo);
        $this->ajeno = $this->crearDetalle($this->otro, $this->periodo);
        $this->crearItems($this->detalle);

        $this->actingAs($this->usuario);
    }

    public function test_el_apartado_muestra_solo_las_boletas_propias(): void
    {
        $this->get('/mis-boletas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MisBoletas/Index')
                ->where('sin_vinculo', false)
                ->where('empleado.dni', $this->dni)
                ->has('boletas', 1)
                ->where('boletas.0.detalle_id', $this->detalle->id));
    }

    public function test_sin_empleado_vinculado_muestra_aviso_y_sin_boletas(): void
    {
        $otroDni = $this->dniLibre();
        $sinVinculo = User::create([
            'username' => $otroDni,
            'dni' => $otroDni,
            'password' => Hash::make('clave123'),
            'rol_id' => 'ROL012',
            'is_active' => true,
        ]);

        $this->actingAs($sinVinculo)
            ->get('/mis-boletas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MisBoletas/Index')
                ->where('sin_vinculo', true)
                ->where('empleado', null)
                ->has('boletas', 0));
    }

    public function test_muestra_solo_la_boleta_propia(): void
    {
        $this->getJson("/mis-boletas/{$this->detalle->id}")
            ->assertOk()
            ->assertJsonPath('trabajador.dni', $this->dni)
            ->assertJsonPath('totales.neto_pagar', 2700);

        $this->getJson("/mis-boletas/{$this->ajeno->id}")->assertStatus(404);
    }

    public function test_descarga_el_pdf_propio_y_no_el_ajeno(): void
    {
        $response = $this->get("/mis-boletas/{$this->detalle->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->get("/mis-boletas/{$this->ajeno->id}/pdf")
            ->assertRedirect('/mis-boletas');
    }

    public function test_revisar_es_idempotente_y_solo_en_boleta_propia(): void
    {
        $this->postJson("/mis-boletas/{$this->ajeno->id}/revisar")->assertStatus(404);

        $this->postJson("/mis-boletas/{$this->detalle->id}/revisar")
            ->assertOk()
            ->assertJsonPath('message', 'Boleta confirmada como revisada');

        $this->assertNotNull($this->detalle->refresh()->revisada_en);

        $anterior = now()->subDays(3)->startOfSecond();
        $this->detalle->update(['revisada_en' => $anterior]);

        $this->postJson("/mis-boletas/{$this->detalle->id}/revisar")->assertOk();

        $this->assertSame(
            $anterior->toDateTimeString(),
            $this->detalle->refresh()->revisada_en->toDateTimeString()
        );
    }

    public function test_las_boletas_de_periodos_no_visibles_no_se_muestran(): void
    {
        $this->periodo->update(['estado' => 'BORRADOR']);

        $this->get('/mis-boletas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('boletas', 0));

        $this->getJson("/mis-boletas/{$this->detalle->id}")->assertStatus(404);
    }

    public function test_invitados_son_redirigidos_al_login(): void
    {
        auth()->logout();

        $this->get('/mis-boletas')->assertRedirect('/login');
        $this->getJson("/mis-boletas/{$this->detalle->id}")->assertStatus(401);
    }

    /**
     * Empleado CAS ACTIVO con person, sin boletas en periodos visibles (así el
     * listado del apartado es predecible aunque la BD de desarrollo tenga datos).
     */
    private function empleadoActivo(?string $exceptoId): Employee
    {
        $conBoletasVisibles = PlanillaDetalle::query()
            ->join('planilla_periodos', 'planilla_periodos.id', '=', 'planilla_detalles.periodo_id')
            ->whereIn('planilla_periodos.estado', PlanillaPeriodo::ESTADOS_BOLETA_VISIBLE)
            ->select('planilla_detalles.employee_id');

        // Sin cuenta de usuario (users.person_id es UNIQUE): la prueba crea
        // la cuenta ROL012 del empleado en el setUp.
        $conUsuario = User::whereNotNull('person_id')->select('person_id');

        return Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', fn ($query) => $query->whereRaw('UPPER(nombre) = ?', ['CAS']))
            ->whereHas('person')
            ->whereNotIn('id', $conBoletasVisibles)
            ->whereNotIn('person_id', $conUsuario)
            ->when($exceptoId, fn ($query) => $query->where('id', '!=', $exceptoId))
            ->inRandomOrder()
            ->firstOrFail();
    }

    /** DNI de 8 dígitos libre en people y users (columnas UNIQUE). */
    private function dniLibre(): string
    {
        foreach (range(1, 50) as $intento) {
            $dni = (string) random_int(48000000, 48999999);

            if (!Person::where('dni', $dni)->exists() && !User::where('dni', $dni)->exists()) {
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
}
