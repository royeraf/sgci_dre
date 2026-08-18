<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_examen_alternativas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pregunta_id');
            $table->string('texto', 500);
            $table->boolean('es_correcta')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('pregunta_id')
                  ->references('id')->on('utilitario_examen_preguntas')
                  ->onDelete('cascade');

            $table->index(['pregunta_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_examen_alternativas');
    }
};
