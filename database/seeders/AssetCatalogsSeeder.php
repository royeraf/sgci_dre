<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetBrand;
use App\Models\AssetColor;
use App\Models\AssetState;
use App\Models\AssetOrigin;

class AssetCatalogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===== MARCAS =====
        $brands = [
            'DELL',
            'HP',
            'LENOVO',
            'ASUS',
            'ACER',
            'SAMSUNG',
            'LG',
            'EPSON',
            'CANON',
            'BROTHER',
            'XEROX',
            'CISCO',
            'APPLE',
            'MICROSOFT',
            'LOGITECH',
            'GENIUS',
            'KINGSTON',
            'SEAGATE',
            'WESTERN DIGITAL',
            'TOSHIBA',
            'SONY',
            'PANASONIC',
            'PHILIPS',
            'GENERAL ELECTRIC',
            'OTROS',
            'SIN MARCA',
        ];

        foreach ($brands as $brand) {
            AssetBrand::firstOrCreate(['nombre' => $brand]);
        }

        // ===== COLORES =====
        $colors = [
            ['nombre' => 'NEGRO', 'codigo_hex' => '#000000'],
            ['nombre' => 'BLANCO', 'codigo_hex' => '#FFFFFF'],
            ['nombre' => 'GRIS', 'codigo_hex' => '#808080'],
            ['nombre' => 'PLATA', 'codigo_hex' => '#C0C0C0'],
            ['nombre' => 'AZUL', 'codigo_hex' => '#0000FF'],
            ['nombre' => 'ROJO', 'codigo_hex' => '#FF0000'],
            ['nombre' => 'VERDE', 'codigo_hex' => '#008000'],
            ['nombre' => 'AMARILLO', 'codigo_hex' => '#FFFF00'],
            ['nombre' => 'NARANJA', 'codigo_hex' => '#FFA500'],
            ['nombre' => 'MARRÓN', 'codigo_hex' => '#8B4513'],
            ['nombre' => 'BEIGE', 'codigo_hex' => '#F5F5DC'],
            ['nombre' => 'CREMA', 'codigo_hex' => '#FFFDD0'],
            ['nombre' => 'DORADO', 'codigo_hex' => '#FFD700'],
            ['nombre' => 'PLATEADO', 'codigo_hex' => '#C0C0C0'],
            ['nombre' => 'TRANSPARENTE', 'codigo_hex' => null],
            ['nombre' => 'MULTICOLOR', 'codigo_hex' => null],
            ['nombre' => 'OTROS', 'codigo_hex' => null],
        ];

        foreach ($colors as $color) {
            AssetColor::firstOrCreate(
                ['nombre' => $color['nombre']],
                ['codigo_hex' => $color['codigo_hex']]
            );
        }

        // ===== ESTADOS =====
        $states = [
            ['nombre' => 'BUENO', 'descripcion' => 'El bien se encuentra en óptimas condiciones de uso', 'orden' => 1],
            ['nombre' => 'REGULAR', 'descripcion' => 'El bien presenta desgaste pero aún es funcional', 'orden' => 2],
            ['nombre' => 'MALO', 'descripcion' => 'El bien tiene daños significativos que afectan su funcionamiento', 'orden' => 3],
            ['nombre' => 'BAJA', 'descripcion' => 'El bien ha sido dado de baja y no está en uso', 'orden' => 4],
            ['nombre' => 'EN REPARACIÓN', 'descripcion' => 'El bien está siendo reparado', 'orden' => 5],
            ['nombre' => 'PERDIDO', 'descripcion' => 'El bien no ha sido localizado', 'orden' => 6],
        ];

        foreach ($states as $state) {
            AssetState::firstOrCreate(
                ['nombre' => $state['nombre']],
                [
                    'descripcion' => $state['descripcion'],
                    'orden' => $state['orden'],
                ]
            );
        }

        // ===== ORÍGENES =====
        $origins = [
            ['nombre' => 'SIGA', 'descripcion' => 'Registrado en el Sistema Integrado de Gestión Administrativa'],
            ['nombre' => 'SOBRANTE', 'descripcion' => 'Bien sobrante no registrado en SIGA'],
            ['nombre' => 'DONACIÓN', 'descripcion' => 'Bien recibido por donación'],
            ['nombre' => 'TRANSFERENCIA', 'descripcion' => 'Bien transferido de otra entidad'],
            ['nombre' => 'COMPRA DIRECTA', 'descripcion' => 'Bien adquirido por compra directa'],
            ['nombre' => 'LICITACIÓN', 'descripcion' => 'Bien adquirido por proceso de licitación'],
            ['nombre' => 'CONVENIO', 'descripcion' => 'Bien recibido por convenio interinstitucional'],
            ['nombre' => 'OTROS', 'descripcion' => 'Otro tipo de origen'],
        ];

        foreach ($origins as $origin) {
            AssetOrigin::firstOrCreate(
                ['nombre' => $origin['nombre']],
                ['descripcion' => $origin['descripcion']]
            );
        }

        $this->command->info('✅ Catálogos de patrimonio creados exitosamente.');
    }
}
