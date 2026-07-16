<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_eventos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            $table->string('tipo', 30)->default('curso');
            $table->text('descripcion')->nullable();
            $table->string('modalidad', 20)->default('presencial');
            $table->string('lugar', 255)->nullable();
            $table->string('enlace_virtual', 255)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->unsignedInteger('cupo_maximo')->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_eventos');
    }
};
