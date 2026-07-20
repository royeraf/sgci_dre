<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_examen_intentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('examen_id');
            $table->uuid('inscripcion_id');
            $table->unsignedInteger('numero_intento');
            $table->timestamp('iniciado_en')->useCurrent();
            $table->timestamp('finalizado_en')->nullable();
            $table->unsignedInteger('aciertos')->nullable();
            $table->unsignedInteger('total_preguntas')->nullable();
            $table->decimal('puntaje', 4, 2)->nullable();
            $table->timestamps();

            $table->foreign('examen_id')
                  ->references('id')->on('utilitario_examenes')
                  ->onDelete('cascade');

            $table->foreign('inscripcion_id')
                  ->references('id')->on('utilitario_inscripciones')
                  ->onDelete('cascade');

            $table->unique(['inscripcion_id', 'examen_id', 'numero_intento'], 'examen_intentos_inscripcion_examen_numero_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_examen_intentos');
    }
};
