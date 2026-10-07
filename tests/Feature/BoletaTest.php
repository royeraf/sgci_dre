<?php

namespace Tests\Feature;

use App\Models\DreConfiguracion;
use App\Models\Employee;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaDetalleItem;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaTardanza;
use App\Services\Planilla\BoletaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La suite corre contra la base de datos de desarrollo (phpunit.xml no fuerza
 * sqlite), así que cada test se envuelve en una transacción que se revierte.
 */
class BoletaTest extends TestCase
{
    use DatabaseTransactions;

    private const ANIO = 2098;

    private const MES = 11;

    private PlanillaPeriodo $periodo;

    private PlanillaDetalle $detalle;

    private Employee $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empleado = Employee::whereHas(
            'contractType',
            fn ($q) => $q->whereRaw('UPPER(nombre) = ?', ['CAS'])
        )->where('estado', 'ACTIVO')->firstOrFail();

        $this->periodo = PlanillaPeriodo::create([
            'anio' => self::ANIO,
            'mes' => self::MES,
            'fecha_inicio' => now()->startOfYear(),
            'fecha_fin' => now()->startOfYear()->endOfYear(),
            'estado' => 'CALCULADA',
        ]);

        $this->detalle = $this->periodo->detalles()->create([
            'employee_id' => $this->empleado->id,
            'remuneracion_base' => 3000,
            'total_ingresos' => 3000,
            'total_descuentos' => 300,
            'total_aportaciones' => 270,
            'neto_pagar' => 2700,
        ]);

        $this->detalle->items()->createMany([
            ['tipo' => 'INGRESO', 'descripcion' => 'Remuneraciones DL 1057', 'base_calculo' => 3000, 'porcentaje' => null, 'monto' => 3000, 'orden' => 1],
            ['tipo' => 'DESCUENTO', 'descripcion' => 'Ley 19990 (ONP)', 'base_calculo' => 3000, 'porcentaje' => 0.13, 'monto' => 300, 'orden' => 2],
            ['tipo' => 'APORTACION', 'descripcion' => 'Essalud', 'base_calculo' => 3000, 'porcentaje' => 0.09, 'monto' => 270, 'orden' => 3],
        ]);

        $this->actingAs($this->admin());
    }

    public function test_lista_los_periodos_con_su_conteo_de_boletas(): void
    {
        $this->getJson('/planillas/boletas')
            ->assertOk()
            ->assertJsonPath('0.id', $this->periodo->id)
            ->assertJsonPath('0.boletas', 1);
    }

    public function test_lista_las_boletas_del_periodo_con_codigo_derivado(): void
    {
        $this->getJson("/planillas/boletas/periodo/{$this->periodo->id}")
            ->assertOk()
            ->assertJsonPath('boletas.0.codigo_boleta', 'BOL-2098-11-0001')
            ->assertJsonPath('boletas.0.detalle_id', $this->detalle->id)
            ->assertJsonPath('boletas.0.neto_pagar', 2700);
    }

    public function test_periodo_sin_planilla_devuelve_422(): void
    {
        $periodo = PlanillaPeriodo::create([
            'anio' => self::ANIO,
            'mes' => 9,
            'estado' => 'BORRADOR',
        ]);

        $this->getJson("/planillas/boletas/periodo/{$periodo->id}")
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_la_boleta_incluye_todas_las_secciones_exigidas(): void
    {
        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertOk()
            ->assertJsonStructure([
                'empresa' => ['ruc', 'razon_social', 'direccion'],
                'periodo' => ['anio', 'mes', 'nombre_mes', 'nombre_periodo'],
                'trabajador' => ['codigo_boleta', 'dni', 'apellidos_nombres', 'fecha_ingreso', 'contrato'],
                'relacion_laboral' => [
                    'cargo', 'regimen', 'periodicidad', 'sistema_pensiones',
                    'cuspp', 'dias_laborados', 'cuenta_ahorro', 'banco',
                ],
                'remuneraciones',
                'retenciones',
                'aportaciones',
                'totales' => ['remuneraciones', 'retenciones', 'aportaciones', 'neto_pagar'],
                'fecha_emision',
            ])
            ->assertJsonPath('relacion_laboral.periodicidad', 'Mensual')
            ->assertJsonPath('totales.neto_pagar', 2700)
            ->assertJsonCount(1, 'remuneraciones')
            ->assertJsonCount(1, 'retenciones')
            ->assertJsonCount(1, 'aportaciones');
    }

    public function test_el_porcentaje_de_los_conceptos_se_expone_en_base_uno(): void
    {
        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertOk()
            ->assertJsonPath('retenciones.0.porcentaje', 0.13)
            ->assertJsonPath('remuneraciones.0.porcentaje', null);
    }

    public function test_dias_laborados_restan_las_faltas_no_justificadas(): void
    {
        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertJsonPath('relacion_laboral.dias_laborados', BoletaService::DIAS_MES);

        PlanillaTardanza::create([
            'periodo_id' => $this->periodo->id,
            'employee_id' => $this->empleado->id,
            'fecha' => now(),
            'dias' => 2,
            'minutos' => 0,
            'total' => 200,
            'justificado' => false,
        ]);

        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertJsonPath('relacion_laboral.dias_laborados', BoletaService::DIAS_MES - 2);
    }

    public function test_las_faltas_justificadas_no_descontan_dias(): void
    {
        PlanillaTardanza::create([
            'periodo_id' => $this->periodo->id,
            'employee_id' => $this->empleado->id,
            'fecha' => now(),
            'dias' => 5,
            'minutos' => 0,
            'total' => 500,
            'justificado' => true,
        ]);

        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertJsonPath('relacion_laboral.dias_laborados', BoletaService::DIAS_MES);
    }

    public function test_sin_fecha_fin_el_contrato_es_indeterminado(): void
    {
        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertJsonPath('trabajador.contrato', BoletaService::CONTRATO_INDETERMINADO);
    }

    public function test_con_fecha_fin_el_contrato_es_fijo(): void
    {
        $original = $this->empleado->fecha_fin_contrato;
        $this->empleado->forceFill(['fecha_fin_contrato' => now()->addMonth()])->save();

        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertJsonPath('trabajador.contrato', BoletaService::CONTRATO_FIJO)
            ->assertJsonStructure(['trabajador' => ['fecha_fin_contrato']]);

        $this->empleado->forceFill(['fecha_fin_contrato' => $original])->save();
    }

    public function test_genera_el_pdf_de_la_boleta(): void
    {
        $response = $this->get("/planillas/boletas/{$this->detalle->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_genera_un_zip_con_un_pdf_por_trabajador(): void
    {
        $response = $this->get("/planillas/boletas/periodo/{$this->periodo->id}/pdf-zip");

        $response->assertOk();
        $this->assertStringContainsString('.zip', $response->headers->get('content-disposition'));

        // BinaryFileResponse::getContent() devuelve false: hay que capturarlo.
        ob_start();
        $response->sendContent();
        $archivo = ob_get_clean();

        $ruta = tempnam(sys_get_temp_dir(), 'zip_test_');
        file_put_contents($ruta, $archivo);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($ruta) === true);
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('boleta_BOL-2098-11-0001.pdf', $zip->getNameIndex(0));
        $zip->close();

        unlink($ruta);
    }

    public function test_lectura_de_la_configuracion_de_la_empresa(): void
    {
        $this->getJson('/planillas/boletas/configuracion')
            ->assertOk()
            ->assertJsonStructure(['ruc', 'razon_social', 'direccion', 'nombre_abreviado']);
    }

    public function test_actualiza_la_configuracion_de_la_empresa(): void
    {
        $this->putJson('/planillas/boletas/configuracion', [
            'ruc' => '20987654321',
            'razon_social' => 'DRE de Prueba',
            'nombre_abreviado' => 'DRE Prueba',
            'direccion' => 'Jr. Falsa 123',
        ])
            ->assertOk()
            ->assertJsonPath('empresa.ruc', '20987654321')
            ->assertJsonPath('empresa.razon_social', 'DRE de Prueba');

        $this->assertDatabaseHas('dre_configuracion', ['ruc' => '20987654321']);
    }

    public function test_rechaza_un_ruc_invalido(): void
    {
        $this->putJson('/planillas/boletas/configuracion', [
            'ruc' => '123',
            'razon_social' => 'X',
            'direccion' => 'Y',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ruc');
    }

    public function test_los_datos_de_empresa_se_reflejan_en_la_boleta(): void
    {
        DreConfiguracion::query()->update([
            'ruc' => '20111111111',
            'razon_social' => 'Direccion Regional de Prueba',
        ]);

        $this->getJson("/planillas/boletas/{$this->detalle->id}")
            ->assertJsonPath('empresa.ruc', '20111111111')
            ->assertJsonPath('empresa.razon_social', 'Direccion Regional de Prueba');
    }

    public function test_los_codigos_de_boleta_son_unicos_y_correlativos_en_el_periodo(): void
    {
        $segundo = $this->periodo->detalles()->create([
            'employee_id' => Employee::where('id', '!=', $this->empleado->id)->firstOrFail()->id,
            'remuneracion_base' => 2000,
            'total_ingresos' => 2000,
            'total_descuentos' => 0,
            'total_aportaciones' => 180,
            'neto_pagar' => 2000,
        ]);

        $boletas = $this->getJson("/planillas/boletas/periodo/{$this->periodo->id}")
            ->assertOk()
            ->json('boletas');

        $this->assertCount(2, $boletas);
        $this->assertCount(2, array_unique(array_column($boletas, 'codigo_boleta')));
        $this->assertContains($segundo->id, array_column($boletas, 'detalle_id'));
    }

    public function test_el_pdf_usa_una_sola_tipografia_dejavu(): void
    {
        // `font-weight: 900` no lo resuelve DomPDF y cae a la serif por
        // defecto (Times), dejando la boleta impresa con dos tipografías.
        $pdf = $this->get("/planillas/boletas/{$this->detalle->id}/pdf")->getContent();

        preg_match_all('#/BaseFont\s*/([A-Za-z0-9+\-]+)#', $pdf, $coincidencias);

        $familias = array_values(array_unique($coincidencias[1]));

        $ajenas = array_values(array_filter(
            $familias,
            fn (string $f) => ! str_contains($f, 'DejaVu')
        ));

        $this->assertSame(
            [],
            $ajenas,
            'El PDF incrusta tipografías ajenas a DejaVu Sans: ' . implode(', ', $ajenas)
        );

        $this->assertContains('DejaVuSans', array_map(
            fn (string $f) => preg_replace('/^[A-Z]{6}\+/', '', $f),
            $familias
        ));
    }

    public function test_las_boletas_requieren_autenticacion(): void
    {
        auth()->logout();

        $this->getJson('/planillas/boletas')->assertStatus(401);
        $this->getJson("/planillas/boletas/{$this->detalle->id}")->assertStatus(401);
    }

    private function admin()
    {
        return \App\Models\User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
