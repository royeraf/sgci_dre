<?php

namespace Database\Seeders;

use App\Models\HrDirection;
use App\Models\HrOffice;
use Illuminate\Database\Seeder;

class HrOfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            ['nombre' => 'ABASTECIMIENTOS', 'codigo' => 'ABA-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'ADMINISTRACIÓN', 'codigo' => 'ADM-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'ALMACÉN', 'codigo' => 'ALM-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'ARCHIVO', 'codigo' => 'ARC-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'ASESORÍA JURÍDICA', 'codigo' => 'AJ-OFF-01', 'direction_codigo' => 'AJ-001'],
            ['nombre' => 'CONSTANCIAS DE PAGOS', 'codigo' => 'CP-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'INFORMÁTICA', 'codigo' => 'INF-001', 'direction_codigo' => 'DGI-001'],
            ['nombre' => 'SIAGIE', 'codigo' => 'SIA-001', 'direction_codigo' => 'DGP-001'],
            ['nombre' => 'TESORERÍA', 'codigo' => 'TES-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'SECRETARÍA GENERAL', 'codigo' => 'SG-001', 'direction_codigo' => 'DG-001'],
            ['nombre' => 'RELACIONES PÚBLICAS', 'codigo' => 'RRPP-001', 'direction_codigo' => 'DG-001'],
            ['nombre' => 'PLANILLAS', 'codigo' => 'PLA-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'PLANIFICACIÓN', 'codigo' => 'PLAN-001', 'direction_codigo' => 'DGI-001'],
            ['nombre' => 'INFRAESTRUCTURA', 'codigo' => 'INFR-001', 'direction_codigo' => 'DGI-001'],
            ['nombre' => 'MESA DE PARTES', 'codigo' => 'MP-001', 'direction_codigo' => 'DG-001'],
            ['nombre' => 'ESCALAFÓN', 'codigo' => 'ESC-001', 'direction_codigo' => 'RRHH-001'],
            ['nombre' => 'CONTABILIDAD', 'codigo' => 'CONT-001', 'direction_codigo' => 'DGA-001'],
            ['nombre' => 'CONTROL INTERNO', 'codigo' => 'CI-001', 'direction_codigo' => 'OCI-001'],
        ];

        foreach ($offices as $office) {
            $direction = null;
            if ($office['direction_codigo']) {
                $direction = HrDirection::where('codigo', $office['direction_codigo'])->first();
            }

            HrOffice::updateOrCreate(
                ['nombre' => $office['nombre']],
                [
                    'codigo' => $office['codigo'],
                    'direction_id' => $direction ? $direction->id : null,
                    'activo' => true
                ]
            );
        }
    }
}
