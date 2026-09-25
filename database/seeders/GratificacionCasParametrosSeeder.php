<?php

namespace Database\Seeders;

use App\Models\GratificacionCasParametro;
use Illuminate\Database\Seeder;

class GratificacionCasParametrosSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            ['anio_fiscal' => 2026, 'porcentaje' => 0.10, 'monto_minimo' => 300.00, 'fecha_vigencia_norma' => '2026-04-01'],
            ['anio_fiscal' => 2027, 'porcentaje' => 0.20, 'monto_minimo' => null, 'fecha_vigencia_norma' => null],
            ['anio_fiscal' => 2028, 'porcentaje' => 0.30, 'monto_minimo' => null, 'fecha_vigencia_norma' => null],
            ['anio_fiscal' => 2029, 'porcentaje' => 0.50, 'monto_minimo' => null, 'fecha_vigencia_norma' => null],
            ['anio_fiscal' => 2030, 'porcentaje' => 1.00, 'monto_minimo' => null, 'fecha_vigencia_norma' => null],
        ];

        foreach ($parametros as $parametro) {
            GratificacionCasParametro::firstOrCreate(
                ['anio_fiscal' => $parametro['anio_fiscal']],
                $parametro
            );
        }

        $this->command->info('✓ Parámetros de gratificación CAS creados (2026-2030)');
    }
}
