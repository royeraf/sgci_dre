<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Descuento por faltas y tardanzas: un registro por empleado y día.
     * Hoja «Dscto. Tard.» de la planilla CAS (registro manual; el origen
     * ASISTENCIA se usará con la integración de Asistencias, Fase 7).
     */
    public function up(): void
    {
        Schema::create('planilla_tardanzas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('periodo_id');
            $table->uuid('employee_id');
            $table->date('fecha');
            $table->unsignedSmallInteger('dias')->default(0);
            $table->unsignedInteger('minutos')->default(0);
            $table->decimal('valor_dia', 10, 2)->default(0);
            $table->decimal('valor_minuto', 10, 4)->default(0);
            $table->decimal('monto_dias', 10, 2)->default(0);
            $table->decimal('monto_minutos', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('origen', 16)->default('MANUAL');
            $table->boolean('justificado')->default(false);
            $table->string('observacion')->nullable();
            $table->uuid('registrado_por')->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'employee_id', 'fecha'], 'pt_periodo_employee_fecha_unique');
            $table->index(['employee_id', 'fecha'], 'pt_employee_fecha_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_tardanzas');
    }
};
