<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas_asistencia', function (Blueprint $table) {
            $table->id();
            $table->char('employee_id', 36);
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->date('fecha');
            $table->time('entrada')->nullable();           // Entrada (inicio de jornada)
            $table->time('salida_mediodia')->nullable();   // Salida a la hora del almuerzo
            $table->time('retorno_mediodia')->nullable();  // Retorno del almuerzo
            $table->time('salida')->nullable();            // Salida al final de la jornada
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas_asistencia');
    }
};
