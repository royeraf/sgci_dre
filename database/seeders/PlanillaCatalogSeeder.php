<?php

namespace Database\Seeders;

use App\Models\PlanillaBanco;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaRegimenPensionario;
use Illuminate\Database\Seeder;

class PlanillaCatalogSeeder extends Seeder
{
    /**
     * Catálogos base de planilla extraídos de la boleta CAS - Sede
     * (Planilla N° 0042 - Agosto).
     */
    public function run(): void
    {
        $this->seedRegimenesPensionarios();
        $this->seedConceptos();
        $this->seedBancos();
    }

    private function seedBancos(): void
    {
        $bancos = [
            ['nombre' => 'Banco de la Nación', 'codigo' => 'BN'],
            ['nombre' => 'Banco de Crédito del Perú', 'codigo' => 'BCP'],
            ['nombre' => 'BBVA', 'codigo' => 'BBVA'],
            ['nombre' => 'Interbank', 'codigo' => 'IBK'],
            ['nombre' => 'Scotiabank', 'codigo' => 'SCO'],
            ['nombre' => 'Banco Pichincha', 'codigo' => 'PIC'],
            ['nombre' => 'Banco Interamericano de Finanzas', 'codigo' => 'BIF'],
            ['nombre' => 'Mibanco', 'codigo' => 'MIB'],
            ['nombre' => 'Banco Falabella', 'codigo' => 'FAL'],
        ];

        foreach ($bancos as $banco) {
            PlanillaBanco::updateOrCreate(
                ['nombre' => $banco['nombre']],
                $banco + ['activo' => true]
            );
        }

        $this->command->info('✓ Bancos creados');
    }

    private function seedRegimenesPensionarios(): void
    {
        // ONP (Ley 19990): 13%. AFP: fondo 10% + prima 1.37% + comisión propia.
        $regimenes = [
            [
                'nombre' => 'ONP (Ley 19990)',
                'tipo' => 'ONP',
                'aporte_obligatorio' => 0.13000,
                'prima_seguro' => 0,
                'comision_fija' => 0,
            ],
            [
                'nombre' => 'AFP Habitat',
                'tipo' => 'AFP',
                'aporte_obligatorio' => 0.10000,
                'prima_seguro' => 0.01370,
                'comision_fija' => 0.01470,
            ],
            [
                'nombre' => 'AFP Integra',
                'tipo' => 'AFP',
                'aporte_obligatorio' => 0.10000,
                'prima_seguro' => 0.01370,
                'comision_fija' => 0.01550,
            ],
            [
                'nombre' => 'AFP Prima',
                'tipo' => 'AFP',
                'aporte_obligatorio' => 0.10000,
                'prima_seguro' => 0.01370,
                'comision_fija' => 0,
            ],
            [
                'nombre' => 'AFP Profuturo',
                'tipo' => 'AFP',
                'aporte_obligatorio' => 0.10000,
                'prima_seguro' => 0.01370,
                'comision_fija' => 0,
            ],
        ];

        foreach ($regimenes as $regimen) {
            PlanillaRegimenPensionario::updateOrCreate(
                ['nombre' => $regimen['nombre']],
                $regimen + ['activo' => true]
            );
        }

        $this->command->info('✓ Regímenes pensionarios creados');
    }

    private function seedConceptos(): void
    {
        $afectosRemunerativos = [
            'afecto_renta5' => false,
            'afecto_essalud' => true,
            'afecto_onp' => true,
            'afecto_afp' => true,
        ];

        $conceptos = [
            // ===== INGRESOS =====
            ['REM_DL1057', 'Remuneraciones DL 1057', 'INGRESO', 'BASE', false, null, $afectosRemunerativos, 1],
            ['DS311_2022', 'DS 311-2022-EF', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 2],
            ['DS313_2023', 'DS 313-2023', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 3],
            ['DS265_279_2024', 'DS 265 y 279-2024', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 4],
            ['DS327_2025', 'DS 327-2025', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 5],
            ['AGUINALDO', 'Aguinaldo Julio/Diciembre', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 6],
            ['REM_VACACIONAL', 'Remuneración Vacacional', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 7],
            ['VAC_TRUNCAS', 'Vacaciones Truncas', 'INGRESO', 'BONIFICACION', false, null, $afectosRemunerativos, 8],
            ['SUBCAFAE', 'Sub CAFAE', 'INGRESO', 'NO_REMUNERATIVO', false, null, [
                'afecto_renta5' => false,
                'afecto_essalud' => false,
                'afecto_onp' => false,
                'afecto_afp' => false,
            ], 9],

            // ===== DESCUENTOS / RETENCIONES =====
            ['AFP_FONDO', 'AFP Fondo', 'DESCUENTO', 'AFP', true, 0.10000, $this->sinAfectacion(), 1],
            ['AFP_COMISION', 'AFP Comisión', 'DESCUENTO', 'AFP', true, null, $this->sinAfectacion(), 2],
            ['AFP_SEGURO', 'AFP Seguro', 'DESCUENTO', 'AFP', true, 0.01370, $this->sinAfectacion(), 3],
            ['ONP_19990', 'Ley 19990 (ONP)', 'DESCUENTO', 'ONP', true, 0.13000, $this->sinAfectacion(), 4],
            // N = E - L en la hoja «Dscto. Tard.»: las faltas/tardanzas reducen
            // la base imponible de EsSalud, AFP y ONP (como en el Excel).
            ['FALTAS_TARDANZAS', 'Faltas / Tardanzas', 'DESCUENTO', 'TARDANZA', false, null, [
                'afecto_renta5' => false,
                'afecto_essalud' => true,
                'afecto_onp' => true,
                'afecto_afp' => true,
            ], 5],
            ['RENTA_4TA', 'Retención Renta 4ta', 'DESCUENTO', 'RENTA', true, 0.08000, $this->sinAfectacion(), 6],
            ['LIC_ESSALUD', 'Licencia a cta. Essalud', 'DESCUENTO', 'OTROS', false, null, $this->sinAfectacion(), 7],
            ['PAGO_INDEBIDO', 'Pago Indebido', 'DESCUENTO', 'OTROS', false, null, $this->sinAfectacion(), 8],

            // ===== APORTACIONES DEL EMPLEADOR =====
            ['ESSALUD', 'Essalud', 'APORTACION', 'ESSALUD', true, 0.09000, $this->sinAfectacion(), 1],
        ];

        foreach ($conceptos as [$codigo, $nombre, $tipo, $categoria, $esPorcentaje, $valor, $afectos, $orden]) {
            PlanillaConcepto::updateOrCreate(
                ['codigo' => $codigo],
                array_merge([
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'categoria' => $categoria,
                    'es_porcentaje' => $esPorcentaje,
                    'valor' => $valor,
                    'orden' => $orden,
                    'activo' => true,
                ], $afectos)
            );
        }

        $this->command->info('✓ Conceptos de planilla creados');
    }

    private function sinAfectacion(): array
    {
        return [
            'afecto_renta5' => false,
            'afecto_essalud' => false,
            'afecto_onp' => false,
            'afecto_afp' => false,
        ];
    }
}
