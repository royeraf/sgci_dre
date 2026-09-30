<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mef_clasificador_gasto', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('anio');
            $table->string('codigo', 30);
            $table->string('codigo_alias', 30)->nullable();
            $table->string('codigo_padre', 30)->nullable();
            $table->unsignedTinyInteger('nivel')->nullable();
            $table->string('descripcion', 500);
            $table->boolean('es_terminal')->default(false);
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('fuente', 500)->nullable();
            $table->string('version_catalogo', 100)->nullable();
            $table->timestamps();

            $table->unique(['anio', 'codigo'], 'mef_anio_codigo_unique');
            $table->index(['anio', 'codigo_padre'], 'mef_anio_padre_index');
            $table->index(['anio', 'nivel'], 'mef_anio_nivel_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mef_clasificador_gasto');
    }
};
