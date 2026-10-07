<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mef_reglas_clasificacion_gasto', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('anio');
            $table->string('regimen', 50)->nullable();
            $table->string('modalidad', 100)->nullable();
            $table->string('concepto_codigo', 100);
            $table->uuid('clasificador_id');
            $table->unsignedInteger('prioridad')->default(100);
            $table->boolean('activo')->default(true);
            $table->string('fuente', 500)->nullable();
            $table->timestamps();

            $table->index(['anio', 'regimen', 'modalidad'], 'mef_reglas_contexto_index');
            $table->index('clasificador_id', 'mef_reglas_clasificador_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mef_reglas_clasificacion_gasto');
    }
};
