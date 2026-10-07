<?php

namespace Tests\Feature;

use App\Models\PlanillaPeriodo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Resumen Planilla CAS (bloques 2.1.1 13.11 / 13.12) y sus exportaciones.
 * Corre contra la BD de desarrollo dentro de una transacción reversible.
 */
class PlanillaResumenTest extends TestCase
{
    use DatabaseTransactions;

    public function test_resumen_planilla_devuelve_bloques_y_totales_cuadrados(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->getJson('/planillas/resumen-planilla');

        $respuesta->assertOk();

        $datos = $respuesta->json();

        $this->assertArrayHasKey('periodo', $datos);
        $this->assertArrayHasKey('bloques', $datos);
        $this->assertArrayHasKey('totales', $datos);
        $this->assertArrayHasKey('13.11', $datos['bloques']);
        $this->assertArrayHasKey('13.12', $datos['bloques']);

        $bloque = $datos['bloques']['13.11'];
        $nombresIngresos = array_column($bloque['ingresos'], 'nombre');

        $this->assertContains('D.L. 1057', $nombresIngresos);
        $this->assertContains('Aguinaldo', $nombresIngresos);
        $this->assertContains('Reintegro', $nombresIngresos);
        $this->assertContains('DS 265 y 279-2024', $nombresIngresos);
        $this->assertNotContains('DS 279_2024', $nombresIngresos);

        // Cuadre: planilla = ingresos de ambos bloques + aporte Essalud
        $ingresos = $datos['bloques']['13.11']['total_ingresos'] + $datos['bloques']['13.12']['total_ingresos'];
        $this->assertEqualsWithDelta($datos['totales']['planilla'], $ingresos + $datos['totales']['aporte'], 0.01);

        // Cuadre: líquido = planilla - descuentos - aporte
        $liquidoEsperado = $datos['totales']['planilla'] - $datos['totales']['descuento'] - $datos['totales']['aporte'];
        $this->assertEqualsWithDelta($datos['totales']['liquido'], $liquidoEsperado, 0.01);

        // Neto de los bloques = total líquido
        $neto = $datos['bloques']['13.11']['neto'] + $datos['bloques']['13.12']['neto'];
        $this->assertEqualsWithDelta($datos['totales']['liquido'], $neto, 0.01);

        // Los descuentos del bloque cuadran con sus filas
        $sumaDescuentos = array_sum(array_column($bloque['descuentos'], 'monto'));
        $this->assertEqualsWithDelta($bloque['total_descuentos'], $sumaDescuentos, 0.01);
    }

    public function test_resumen_planilla_exporta_xlsx(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->get('/planillas/resumen-planilla/export/xlsx');

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $contenido = $respuesta->streamedContent();
        $this->assertSame('PK', substr($contenido, 0, 2));
        $this->assertStringContainsString('resumen_planilla_', (string) $respuesta->headers->get('content-disposition'));
    }

    public function test_resumen_planilla_exporta_pdf(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->get('/planillas/resumen-planilla/export/pdf');

        $respuesta->assertOk();
        $this->assertStringContainsString('pdf', (string) $respuesta->headers->get('content-type'));

        $contenido = (string) $respuesta->getContent();
        $this->assertSame('%PDF-', substr($contenido, 0, 5));
    }

    public function test_resumen_planilla_rechaza_formato_no_soportado(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/resumen-planilla/export/csv')
            ->assertStatus(422);
    }

    public function test_resumen_de_hoja_resumen_sigue_funcionando(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/planillas/resumen')
            ->assertOk()
            ->assertJsonStructure([
                'periodo',
                'ingresos',
                'total_planillas',
                'descuentos',
                'total_descuentos',
                'abono',
            ]);
    }

    public function test_resumen_planilla_sin_periodos_devuelve_404(): void
    {
        PlanillaPeriodo::query()->delete();

        $this->actingAs($this->admin())
            ->getJson('/planillas/resumen-planilla')
            ->assertStatus(404);
    }

    private function admin()
    {
        return \App\Models\User::where('rol_id', 'ROL001')->firstOrFail();
    }
}
