<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla de oficinas físicas que pertenecen a un área
     */
    public function up(): void
    {
        Schema::create('hr_offices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id');
            $table->string('codigo', 20)->nullable();
            $table->string('nombre', 200);
            $table->string('ubicacion', 100)->nullable();
            $table->string('piso', 10)->nullable();
            $table->string('telefono_interno', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('area_id')
                  ->references('id')
                  ->on('hr_areas')
                  ->onDelete('cascade');

            $table->index('area_id');
            $table->index('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_offices');
    }
};
