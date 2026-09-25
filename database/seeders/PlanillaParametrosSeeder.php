<?php

namespace Database\Seeders;

use App\Models\PlanillaParametro;
use Illuminate\Database\Seeder;

class PlanillaParametrosSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            ['anio' => 2026, 'uit' => 5500.00, 'pct_tope_essalud' => 0.4500, 'rmv' => 1130.00, 'tasa_essalud' => 0.0900],
        ];

        foreach ($parametros as $parametro) {
            PlanillaParametro::firstOrCreate(['anio' => $parametro['anio']], $parametro);
        }

        $this->command->info('✓ Parámetros de planilla creados (2026)');
    }
}
