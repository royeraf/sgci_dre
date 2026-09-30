<?php

namespace Database\Seeders;

use App\Models\MefClasificadorGasto;
use App\Models\MefReglaClasificacionGasto;
use Illuminate\Database\Seeder;

class MefCatalogoSeeder extends Seeder
{
    private const FUENTE = 'RD 0021-2025-EF/50.01 - Anexo 2: Clasificador Económico de Gastos AF 2026 '
        .'(subconjunto suministrado en especificación; pendiente de cotejo con el Anexo 2 oficial)';

    private const VERSION = 'RD 0021-2025-EF/50.01';

    private const PENDIENTE = ' (descripción pendiente de validación)';

    /**
     * Subconjunto mínimo del catálogo: solo códigos y descripciones que
     * provienen de la especificación / fuente oficial citada. Los que no
     * están sustentados quedan marcados como pendientes de validación; los
     * conceptos no listados siguen en PENDIENTE hasta crear su regla.
     */
    public function run(): void
    {
        $catalogo = [
            ['2', null, 1, 'GENÉRICA'.self::PENDIENTE, false],
            ['2.1', '2', 2, 'SUBGENÉRICA'.self::PENDIENTE, false],
            ['2.1.1', '2.1', 3, 'RETRIBUCIONES Y COMPLEMENTOS EN EFECTIVO', false],
            ['2.1.1.13', '2.1.1', 4, 'CONTRATO ADMINISTRATIVO DE SERVICIOS'.self::PENDIENTE, false],
            ['2.1.1.13.1', '2.1.1.13', 5, 'CONTRATO ADMINISTRATIVO DE SERVICIOS'.self::PENDIENTE, false],
            ['2.1.1.13.1.1', '2.1.1.13.1', 6, 'CONTRATO ADMINISTRATIVO DE SERVICIOS - INDETERMINADO', true],
            ['2.1.1.13.1.2', '2.1.1.13.1', 6, 'CONTRATO ADMINISTRATIVO DE SERVICIOS - TRANSITORIO', true],
        ];

        $ids = [];

        foreach ($catalogo as [$codigo, $padre, $nivel, $descripcion, $terminal]) {
            $registro = MefClasificadorGasto::firstOrCreate(
                ['anio' => 2026, 'codigo' => $codigo],
                [
                    'codigo_alias' => $codigo === '2.1.1.13.1.1' ? '2.1.1.13.11' : null,
                    'codigo_padre' => $padre,
                    'nivel' => $nivel,
                    'descripcion' => $descripcion,
                    'es_terminal' => $terminal,
                    'activo' => true,
                    'fuente' => self::FUENTE,
                    'version_catalogo' => self::VERSION,
                ]
            );

            $ids[$codigo] = $registro->id;
        }

        $reglas = [
            ['INDETERMINADO', 'REM_DL1057', '2.1.1.13.1.1'],
            ['TRANSITORIO', 'REM_DL1057', '2.1.1.13.1.2'],
        ];

        foreach ($reglas as [$modalidad, $concepto, $codigo]) {
            MefReglaClasificacionGasto::firstOrCreate(
                [
                    'anio' => 2026,
                    'regimen' => 'CAS',
                    'modalidad' => $modalidad,
                    'concepto_codigo' => $concepto,
                ],
                [
                    'clasificador_id' => $ids[$codigo],
                    'prioridad' => 100,
                    'activo' => true,
                    'fuente' => self::FUENTE,
                ]
            );
        }

        $this->command->info('✓ Catálogo MEF (subconjunto) y reglas CAS creados');
    }
}
