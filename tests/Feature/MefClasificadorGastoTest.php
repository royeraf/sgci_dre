<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\MefClasificadorGasto;
use App\Models\MefClasificadorGastoHistorial;
use App\Models\MefReglaClasificacionGasto;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaPeriodo;
use App\Services\Mef\MefClasificadorGastoService;
use App\Services\Planilla\PlanillaGenerador;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La suite corre contra la base de datos de desarrollo (phpunit.xml no fuerza
 * sqlite), así que cada test se envuelve en una transacción que se revierte.
 * El catálogo de prueba usa el ejercicio 2099 para no tocar el 2026 real.
 */
class MefClasificadorGastoTest extends TestCase
{
    use DatabaseTransactions;

    private const ANIO = 2099;

    private const CODIGO_INDETERMINADO = '2.1.1.13.1.1';

    private const CODIGO_TRANSITORIO = '2.1.1.13.1.2';

    private const FUENTE = 'RD 0021-2025-EF/50.01 - Anexo 2 (prueba)';

    protected function setUp(): void
    {
        parent::setUp();

        $catalogo = [
            ['2', null, 1, 'GENÉRICA', false, true, null],
            ['2.1', '2', 2, 'SUBGENÉRICA', false, true, null],
            ['2.1.1', '2.1', 3, 'RETRIBUCIONES Y COMPLEMENTOS EN EFECTIVO', false, true, null],
            ['2.1.1.13', '2.1.1', 4, 'CONTRATO ADMINISTRATIVO DE SERVICIOS', false, true, null],
            ['2.1.1.13.1', '2.1.1.13', 5, 'CONTRATO ADMINISTRATIVO DE SERVICIOS', false, true, null],
            [self::CODIGO_INDETERMINADO, '2.1.1.13.1', 6, 'CONTRATO ADMINISTRATIVO DE SERVICIOS - INDETERMINADO', true, true, null],
            [self::CODIGO_TRANSITORIO, '2.1.1.13.1', 6, 'CONTRATO ADMINISTRATIVO DE SERVICIOS - TRANSITORIO', true, true, null],
            ['2.1.1.13.1.7', '2.1.1.13.1', 6, 'CODIGO SIN REGLAS', true, true, null],
            ['2.1.1.13.1.8', '2.1.1.13.1', 6, 'CODIGO VENCIDO', true, true, '2020-12-31'],
            ['2.1.1.13.1.9', '2.1.1.13.1', 6, 'CODIGO INACTIVO', true, false, null],
        ];

        foreach ($catalogo as [$codigo, $padre, $nivel, $descripcion, $terminal, $activo, $fechaFin]) {
            MefClasificadorGasto::firstOrCreate(
                ['anio' => self::ANIO, 'codigo' => $codigo],
                [
                    'codigo_alias' => $codigo === self::CODIGO_INDETERMINADO ? '2.1.1.13.11' : null,
                    'codigo_padre' => $padre,
                    'nivel' => $nivel,
                    'descripcion' => $descripcion,
                    'es_terminal' => $terminal,
                    'activo' => $activo,
                    'fecha_fin' => $fechaFin,
                    'fuente' => self::FUENTE,
                ]
            );
        }

        $reglas = [
            ['CAS', 'INDETERMINADO', 'REM_DL1057', self::CODIGO_INDETERMINADO, true],
            ['CAS', 'TRANSITORIO', 'REM_DL1057', self::CODIGO_TRANSITORIO, true],
            ['CAS', null, 'INACT_TEST', '2.1.1.13.1.9', true],
            ['CAS', null, 'VENC_TEST', '2.1.1.13.1.8', true],
            ['CAS', null, 'OFF_TEST', self::CODIGO_INDETERMINADO, false],
        ];

        foreach ($reglas as [$regimen, $modalidad, $concepto, $codigo, $activo]) {
            MefReglaClasificacionGasto::firstOrCreate(
                [
                    'anio' => self::ANIO,
                    'regimen' => $regimen,
                    'modalidad' => $modalidad,
                    'concepto_codigo' => $concepto,
                ],
                [
                    'clasificador_id' => MefClasificadorGasto::where('anio', self::ANIO)->where('codigo', $codigo)->firstOrFail()->id,
                    'prioridad' => 100,
                    'activo' => $activo,
                    'fuente' => self::FUENTE,
                ]
            );
        }
    }

    // ========== SERVICIO ==========

    public function test_cas_indeterminado_se_clasifica_en_el_codigo_esperado(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO, 'CAS', 'INDETERMINADO', 'REM_DL1057');

        $this->assertSame('VALIDADO', $resultado['estado']);
        $this->assertSame(self::CODIGO_INDETERMINADO, $resultado['codigo']);
        $this->assertSame(self::ANIO, $resultado['anio_fiscal']);
    }

    public function test_cas_transitorio_se_clasifica_en_el_codigo_esperado(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO, 'CAS', 'TRANSITORIO', 'REM_DL1057');

        $this->assertSame('VALIDADO', $resultado['estado']);
        $this->assertSame(self::CODIGO_TRANSITORIO, $resultado['codigo']);
    }

    public function test_concepto_sin_regla_queda_pendiente(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO, 'CAS', 'INDETERMINADO', 'ESSALUD');

        $this->assertSame('PENDIENTE', $resultado['estado']);
        $this->assertNull($resultado['codigo']);
        $this->assertNotNull($resultado['motivo']);
    }

    public function test_regla_inactiva_no_se_aplica(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO, 'CAS', 'INDETERMINADO', 'OFF_TEST');

        $this->assertSame('PENDIENTE', $resultado['estado']);
    }

    public function test_clasificador_inactivo_se_observa(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO, 'CAS', null, 'INACT_TEST');

        $this->assertSame('OBSERVADO', $resultado['estado']);
    }

    public function test_clasificador_fuera_de_vigencia_se_observa(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO, 'CAS', null, 'VENC_TEST');

        $this->assertSame('OBSERVADO', $resultado['estado']);
    }

    public function test_el_codigo_de_otro_ejercicio_no_se_reutiliza(): void
    {
        $resultado = $this->mef()->clasificar(self::ANIO - 1, 'CAS', 'INDETERMINADO', 'REM_DL1057');

        $this->assertSame('PENDIENTE', $resultado['estado']);
        $this->assertNull($resultado['codigo']);
    }

    public function test_el_alias_resuelve_al_clasificador(): void
    {
        $clasificador = $this->mef()->buscarCodigo(self::ANIO, '2.1.1.13.11');

        $this->assertNotNull($clasificador);
        $this->assertSame(self::CODIGO_INDETERMINADO, $clasificador->codigo);
    }

    // ========== ENDPOINTS DEL CATÁLOGO ==========

    public function test_index_filtra_por_anio_y_busqueda(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto?anio='.self::ANIO.'&search=TRANSITORIO')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.codigo', self::CODIGO_TRANSITORIO);
    }

    public function test_show_devuelve_el_clasificador_del_codigo(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto/'.self::ANIO.'/'.self::CODIGO_INDETERMINADO)
            ->assertOk()
            ->assertJsonPath('codigo', self::CODIGO_INDETERMINADO)
            ->assertJsonPath('anio', self::ANIO);
    }

    public function test_show_sin_codigo_devuelve_404(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto/'.self::ANIO.'/2.1.1.13.1.99')
            ->assertNotFound();
    }

    public function test_hijos_devuelve_solo_los_directos(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto/'.self::ANIO.'/2.1.1.13.1/hijos')
            ->assertOk()
            ->assertJsonCount(5)
            ->assertJsonPath('0.codigo', '2.1.1.13.1.1');
    }

    public function test_validar_codigo_informado(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto/'.self::ANIO.'/validar/'.self::CODIGO_INDETERMINADO)
            ->assertOk()
            ->assertJsonPath('valido', true)
            ->assertJsonPath('estado', 'VALIDO');

        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto/'.self::ANIO.'/validar/9.9.9')
            ->assertOk()
            ->assertJsonPath('valido', false)
            ->assertJsonPath('estado', 'INEXISTENTE');
    }

    public function test_store_rechaza_formato_de_codigo_invalido(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/planillas/mef/clasificador-gasto', [
                'anio' => self::ANIO,
                'codigo' => 'abc',
                'descripcion' => 'Código inválido',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_store_rechaza_codigo_duplicado(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/planillas/mef/clasificador-gasto', [
                'anio' => self::ANIO,
                'codigo' => self::CODIGO_INDETERMINADO,
                'descripcion' => 'Duplicado',
            ])
            ->assertStatus(422);
    }

    public function test_store_registra_el_clasificador_y_su_historial(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->postJson('/planillas/mef/clasificador-gasto', [
                'anio' => self::ANIO,
                'codigo' => '2.1.1.13.1.10',
                'codigo_padre' => '2.1.1.13.1',
                'nivel' => 6,
                'descripcion' => 'NUEVO CODIGO DE PRUEBA',
                'es_terminal' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('clasificador.codigo', '2.1.1.13.1.10');

        $id = $respuesta->json('clasificador.id');

        $this->assertDatabaseHas('mef_clasificador_gasto_historial', [
            'clasificador_id' => $id,
            'campo' => 'registro',
        ]);
    }

    public function test_update_genera_entrada_de_historial(): void
    {
        $clasificador = MefClasificadorGasto::where('anio', self::ANIO)
            ->where('codigo', '2.1.1.13.1.7')
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->putJson("/planillas/mef/clasificador-gasto/{$clasificador->id}", [
                'anio' => self::ANIO,
                'codigo' => $clasificador->codigo,
                'descripcion' => 'DESCRIPCION ACTUALIZADA EN PRUEBA',
                'es_terminal' => true,
            ])
            ->assertOk();

        $historial = MefClasificadorGastoHistorial::where('clasificador_id', $clasificador->id)
            ->where('campo', 'descripcion')
            ->first();

        $this->assertNotNull($historial);
        $this->assertSame('CODIGO SIN REGLAS', $historial->valor_anterior);
        $this->assertSame('DESCRIPCION ACTUALIZADA EN PRUEBA', $historial->valor_nuevo);
    }

    public function test_destroy_se_bloquea_si_el_clasificador_tiene_reglas(): void
    {
        $clasificador = MefClasificadorGasto::where('anio', self::ANIO)
            ->where('codigo', self::CODIGO_INDETERMINADO)
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->deleteJson("/planillas/mef/clasificador-gasto/{$clasificador->id}")
            ->assertStatus(422);
    }

    public function test_destroy_elimina_un_clasificador_sin_reglas(): void
    {
        $clasificador = MefClasificadorGasto::where('anio', self::ANIO)
            ->where('codigo', '2.1.1.13.1.7')
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->deleteJson("/planillas/mef/clasificador-gasto/{$clasificador->id}")
            ->assertOk();

        $this->assertNull(MefClasificadorGasto::find($clasificador->id));
        $this->assertDatabaseHas('mef_clasificador_gasto_historial', [
            'clasificador_id' => $clasificador->id,
            'campo' => 'eliminado',
        ]);
    }

    public function test_historial_devuelve_los_cambios_del_clasificador(): void
    {
        $creado = $this->actingAs($this->admin())
            ->postJson('/planillas/mef/clasificador-gasto', [
                'anio' => self::ANIO,
                'codigo' => '2.1.1.13.1.11',
                'codigo_padre' => '2.1.1.13.1',
                'descripcion' => 'CODIGO PARA HISTORIAL',
                'es_terminal' => true,
            ])
            ->assertCreated();

        $this->actingAs($this->admin())
            ->getJson('/planillas/mef/clasificador-gasto/'.$creado->json('clasificador.id').'/historial')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.campo', 'registro');
    }

    public function test_las_rutas_mef_requieren_sesion(): void
    {
        $this->getJson('/planillas/mef/clasificador-gasto?anio='.self::ANIO)
            ->assertStatus(401);
    }

    // ========== REGLAS ==========

    public function test_las_reglas_del_ejercicio_se_listan_con_su_codigo(): void
    {
        $reglas = $this->actingAs($this->admin())
            ->getJson('/planillas/mef/reglas?anio='.self::ANIO)
            ->assertOk()
            ->assertJsonCount(5)
            ->json();

        $remuneracion = collect($reglas)->first(
            fn ($regla) => $regla['concepto_codigo'] === 'REM_DL1057' && $regla['modalidad'] === 'INDETERMINADO'
        );

        $this->assertNotNull($remuneracion);
        $this->assertSame('INDETERMINADO', $remuneracion['modalidad']);
        $this->assertSame(self::CODIGO_INDETERMINADO, $remuneracion['codigo']);
    }

    public function test_store_regla_valida_que_el_clasificador_pertenezca_al_anio(): void
    {
        $otroAnio = MefClasificadorGasto::create([
            'anio' => self::ANIO + 1,
            'codigo' => '2.1.1.13.1.1',
            'nivel' => 6,
            'descripcion' => 'OTRO EJERCICIO',
            'es_terminal' => true,
        ]);

        $this->actingAs($this->admin())
            ->postJson('/planillas/mef/reglas', [
                'anio' => self::ANIO,
                'regimen' => 'CAS',
                'concepto_codigo' => 'REM_DL1057',
                'clasificador_id' => $otroAnio->id,
            ])
            ->assertStatus(422);
    }

    // ========== INTEGRACIÓN CON EL MOTOR ==========

    public function test_generar_el_periodo_clasifica_los_items(): void
    {
        $empleado = Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', fn ($q) => $q->whereRaw('UPPER(nombre) = ?', ['CAS']))
            ->whereHas('remunerations', fn ($q) => $q->whereNull('hasta')->where('monto', '>', 0))
            ->firstOrFail();

        $empleado->update([
            'modalidad_cas' => Employee::MODALIDAD_INDETERMINADO,
            'fecha_fin_contrato' => null,
            'fecha_ingreso' => now()->startOfYear()->subYears(2),
            'fecha_inicio_contrato' => null,
        ]);

        $periodo = PlanillaPeriodo::create([
            'anio' => self::ANIO,
            'mes' => 12,
            'fecha_inicio' => now()->startOfYear(),
            'fecha_fin' => now()->startOfYear()->endOfYear(),
            'estado' => 'BORRADOR',
        ]);

        $resultado = app(PlanillaGenerador::class)->generar($periodo);

        $this->assertGreaterThan(0, $resultado['empleados']);

        $conceptoRemuneracion = PlanillaConcepto::where('codigo', 'REM_DL1057')->firstOrFail();
        $detalle = $periodo->detalles()->where('employee_id', $empleado->id)->firstOrFail();

        $itemRemuneracion = $detalle->items()->where('concepto_id', $conceptoRemuneracion->id)->firstOrFail();

        $this->assertSame('VALIDADO', $itemRemuneracion->estado_clasificacion);
        $this->assertSame(self::CODIGO_INDETERMINADO, $itemRemuneracion->clasificador_gasto_codigo);
        $this->assertSame(self::ANIO, $itemRemuneracion->anio_fiscal);

        $this->assertTrue(
            $detalle->items()->where('estado_clasificacion', 'PENDIENTE')->exists(),
            'Los conceptos sin regla deben quedar en PENDIENTE'
        );
    }

    public function test_update_contrato_guarda_la_modalidad_cas(): void
    {
        $empleado = Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', fn ($q) => $q->whereRaw('UPPER(nombre) = ?', ['CAS']))
            ->firstOrFail();

        $inicio = $empleado->fecha_inicio_contrato?->toDateString() ?? now()->toDateString();

        $this->actingAs($this->admin())
            ->putJson("/planillas/empleados/{$empleado->id}/contrato", [
                'fecha_inicio_contrato' => $inicio,
                'fecha_fin_contrato' => null,
                'modalidad_cas' => 'TRANSITORIO',
            ])
            ->assertOk()
            ->assertJsonPath('modalidad_cas', 'TRANSITORIO')
            ->assertJsonPath('modalidad_cas_efectiva', 'TRANSITORIO');

        $this->assertSame('TRANSITORIO', $empleado->fresh()->modalidad_cas);
    }

    public function test_update_contrato_rechaza_modalidad_desconocida(): void
    {
        $empleado = Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', fn ($q) => $q->whereRaw('UPPER(nombre) = ?', ['CAS']))
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->putJson("/planillas/empleados/{$empleado->id}/contrato", [
                'fecha_inicio_contrato' => now()->toDateString(),
                'fecha_fin_contrato' => null,
                'modalidad_cas' => 'CUALQUIERA',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['modalidad_cas']);
    }

    private function mef(): MefClasificadorGastoService
    {
        return app(MefClasificadorGastoService::class);
    }

    private function admin()
    {
        return \App\Models\User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
