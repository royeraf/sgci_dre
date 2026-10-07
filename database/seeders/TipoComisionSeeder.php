<?php

namespace Database\Seeders;

use App\Models\EmployeePayrollProfile;
use Illuminate\Database\Seeder;

class TipoComisionSeeder extends Seeder
{
    private const FLUJO_CUSPP = [
        '511111JUCRT5',
        '527010OPEAE4',
        '552071JSROI5',
        '517540GVBAR4',
        '538480VMOGE7',
    ];

    public function run(): void
    {
        $perfiles = EmployeePayrollProfile::whereNotNull('regimen_pensionario_id')->get();

        $flujo = 0;
        $mixta = 0;

        foreach ($perfiles as $perfil) {
            if ($perfil->regimenPensionario?->tipo !== 'AFP') {
                continue;
            }

            if ($perfil->tipo_comision !== null) {
                continue;
            }

            $esFlujo = $perfil->cuspp !== null
                && in_array(strtoupper(trim($perfil->cuspp)), self::FLUJO_CUSPP, true);

            $perfil->update(['tipo_comision' => $esFlujo ? 'FLUJO' : 'MIXTA']);
            $esFlujo ? $flujo++ : $mixta++;
        }

        $this->command->info("✓ Tipo de comisión: {$flujo} FLUJO, {$mixta} MIXTA");
    }
}
