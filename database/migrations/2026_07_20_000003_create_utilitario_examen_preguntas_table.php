<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_examen_preguntas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('examen_id');
            $table->text('enunciado');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('examen_id')
                  ->references('id')->on('utilitario_examenes')
                  ->onDelete('cascade');

            $table->index(['examen_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_examen_preguntas');
    }
};
