<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movement_types', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('codigo', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Seed default movement types matching existing hardcoded values
        DB::table('asset_movement_types')->insert([
            ['nombre' => 'Asignación',  'codigo' => 'ASIGNACION', 'descripcion' => 'Asignación de bien a un responsable',       'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Devolución',  'codigo' => 'DEVOLUCION', 'descripcion' => 'Devolución de bien por el responsable',     'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Traslado',    'codigo' => 'TRASLADO',   'descripcion' => 'Traslado de bien a otra ubicación',         'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Baja',        'codigo' => 'BAJA',       'descripcion' => 'Baja del bien del inventario activo',       'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movement_types');
    }
};
