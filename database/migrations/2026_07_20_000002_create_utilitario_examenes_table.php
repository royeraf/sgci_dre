<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_examenes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evento_id');
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->unsignedInteger('duracion_minutos');
            $table->unsignedInteger('intentos_permitidos')->default(1);
            $table->decimal('nota_minima_aprobatoria', 4, 2)->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->boolean('examen_habilitado')->default(false);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('evento_id')
                  ->references('id')->on('utilitario_eventos')
                  ->onDelete('cascade');

            $table->index(['evento_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_examenes');
    }
};
