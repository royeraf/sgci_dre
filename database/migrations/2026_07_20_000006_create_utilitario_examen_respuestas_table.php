<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_examen_respuestas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('intento_id');
            $table->uuid('pregunta_id');
            $table->uuid('alternativa_id')->nullable();
            $table->boolean('es_correcta')->default(false);
            $table->timestamps();

            $table->foreign('intento_id')
                  ->references('id')->on('utilitario_examen_intentos')
                  ->onDelete('cascade');

            $table->foreign('pregunta_id')
                  ->references('id')->on('utilitario_examen_preguntas')
                  ->onDelete('cascade');

            $table->foreign('alternativa_id')
                  ->references('id')->on('utilitario_examen_alternativas')
                  ->onDelete('set null');

            $table->unique(['intento_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_examen_respuestas');
    }
};
