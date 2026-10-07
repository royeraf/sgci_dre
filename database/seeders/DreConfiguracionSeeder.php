<?php

namespace Database\Seeders;

use App\Models\DreConfiguracion;
use Illuminate\Database\Seeder;

class DreConfiguracionSeeder extends Seeder
{
    /**
     * Datos de la entidad emisora para el encabezado de la boleta de pago.
     *
     * El RUC de ejemplo debe reemplazarse por el real desde la pestaña
     * Boletas → «Datos de la empresa».
     */
    public function run(): void
    {
        DreConfiguracion::query()->updateOrCreate(
            ['id' => 1],
            [
                'ruc' => '20123456789',
                'razon_social' => 'Dirección Regional de Educación de Huánuco',
                'nombre_abreviado' => 'DRE Huánuco',
                'direccion' => 'Av. La Merced N° 523, Huánuco',
            ]
        );

        $this->command->info('✓ Datos de la empresa registrados');
    }
}
