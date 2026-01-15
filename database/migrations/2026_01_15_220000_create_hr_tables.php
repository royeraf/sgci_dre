<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de empleados
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('dni', 8)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['Masculino', 'Femenino'])->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('correo')->nullable();
            $table->string('cargo')->nullable();
            $table->string('area')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->enum('tipo_contrato', ['Nombrado', 'CAS', 'Locador', 'Practicante'])->default('Nombrado');
            $table->enum('estado', ['ACTIVO', 'INACTIVO', 'LICENCIA', 'VACACIONES'])->default('ACTIVO');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        // Tabla de vacaciones
        Schema::create('vacations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('empleado_id');
            $table->string('dni', 8);
            $table->string('periodo', 10)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_tomados')->default(0);
            $table->integer('dias_pendientes')->default(30);
            $table->enum('estado', ['PROGRAMADO', 'EN CURSO', 'COMPLETADO', 'CANCELADO'])->default('PROGRAMADO');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('empleado_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacations');
        Schema::dropIfExists('employees');
    }
};
