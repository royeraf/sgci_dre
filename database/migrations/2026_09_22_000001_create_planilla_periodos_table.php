<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periodo mensual de planilla CAS (una sola planilla por mes).
     */
    public function up(): void
    {
        Schema::create('planilla_periodos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->enum('estado', ['BORRADOR', 'CALCULADA', 'APROBADA', 'PAGADA', 'CERRADA'])
                ->default('BORRADOR');
            $table->unsignedInteger('total_empleados')->default(0);
            $table->decimal('total_neto', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_periodos');
    }
};
