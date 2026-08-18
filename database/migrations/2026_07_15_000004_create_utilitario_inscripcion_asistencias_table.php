<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_inscripcion_asistencias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inscripcion_id');
            $table->date('fecha');
            $table->timestamp('marcado_en')->useCurrent();
            $table->unsignedBigInteger('marcado_por')->nullable();
            $table->timestamps();

            $table->foreign('inscripcion_id')
                  ->references('id')->on('utilitario_inscripciones')
                  ->onDelete('cascade');

            $table->unique(['inscripcion_id', 'fecha']);
            $table->index(['inscripcion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_inscripcion_asistencias');
    }
};
