<?php

namespace Database\Seeders;

use App\Models\HrDirection;
use Illuminate\Database\Seeder;

class HrDirectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $directions = [
            [
                'nombre' => 'DIRECCIÓN DE GESTIÓN ADMINISTRATIVA',
                'abreviacion' => 'DGA',
                'codigo' => 'DGA-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'DIRECCIÓN DE GESTIÓN INSTITUCIONAL',
                'abreviacion' => 'DGI',
                'codigo' => 'DGI-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'ASESORIA JURÍDICA',
                'abreviacion' => 'AJ',
                'codigo' => 'AJ-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'RECURSOS HUMANOS',
                'abreviacion' => 'RRHH',
                'codigo' => 'RRHH-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'ÓRGANO DE CONTROL INSTITUCIONAL',
                'abreviacion' => 'OCI',
                'codigo' => 'OCI-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'DIRECCIÓN DE GESTIÓN PEDAGÓGICA',
                'abreviacion' => 'DGP',
                'codigo' => 'DGP-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'DIRECCIÓN GENERAL',
                'abreviacion' => 'DG',
                'codigo' => 'GD-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'INSTITUCIONES DE EDUCACIÓN SUPERIOR',
                'abreviacion' => 'IES',
                'codigo' => 'IES-001',
                'descripcion' => null,
                'telefono_interno' => null,
                'ubicacion' => null,
                'activo' => true,
            ],
        ];

        foreach ($directions as $direction) {
            HrDirection::updateOrCreate(
                ['codigo' => $direction['codigo']],
                $direction
            );
        }
    }
}
