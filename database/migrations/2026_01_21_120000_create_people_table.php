<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla base centralizada para todas las personas (internas y externas)
     * Elimina la duplicación de campos como DNI, nombres, apellidos, etc.
     */
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('dni', 8)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['Masculino', 'Femenino'])->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->enum('tipo', ['INTERNO', 'EXTERNO'])->default('EXTERNO');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices para búsquedas frecuentes
            $table->index('dni');
            $table->index('tipo');
            $table->index(['apellidos', 'nombres']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
