<?php

namespace Database\Seeders;

use App\Models\PlanillaComisionAfp;
use App\Models\PlanillaParametroAfp;
use App\Models\PlanillaRegimenPensionario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PlanillaParametrosAfpSeeder extends Seeder
{
    private const APORTE = 0.1000;

    private const PRIMA = 0.0137;

    private const RMA = 12672.65;

    private const COMISIONES = [
        'AFP Habitat' => ['flujo' => 0.0147, 'saldo' => 0.0125],
        'AFP Integra' => ['flujo' => 0.0155, 'saldo' => 0.0078],
        'AFP Prima' => ['flujo' => 0.0160, 'saldo' => 0.0125],
        'AFP Profuturo' => ['flujo' => 0.0169, 'saldo' => 0.0068],
    ];

    public function run(): void
    {
        for ($mes = 1; $mes <= 12; $mes++) {
            $fecha = Carbon::create(2026, $mes, 1);

            $parametro = PlanillaParametroAfp::firstOrCreate(
                ['mes' => $fecha->toDateString()],
                [
                    'aporte_obligatorio' => self::APORTE,
                    'prima_seguro' => self::PRIMA,
                    'remuneracion_maxima_asegurable' => self::RMA,
                ]
            );

            foreach (self::COMISIONES as $nombre => $tasas) {
                $regimen = PlanillaRegimenPensionario::where('nombre', $nombre)->first();

                if (!$regimen) {
                    continue;
                }

                PlanillaComisionAfp::firstOrCreate(
                    [
                        'mes' => $fecha->toDateString(),
                        'regimen_pensionario_id' => $regimen->id,
                    ],
                    [
                        'comision_flujo' => $tasas['flujo'],
                        'comision_saldo' => $tasas['saldo'],
                    ]
                );
            }
        }

        $this->command->info('✓ Parámetros SBS AFP creados (2026: aporte/prima/RMA + comisiones por AFP)');
    }
}
