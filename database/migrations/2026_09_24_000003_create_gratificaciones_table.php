<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gratificaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->unsignedSmallInteger('anio');
            $table->string('periodo', 16);
            $table->decimal('remuneracion_corte', 10, 2)->default(0);
            $table->decimal('porcentaje_aplicado', 5, 4)->default(0);
            $table->unsignedSmallInteger('meses_completos')->default(0);
            $table->unsignedSmallInteger('dias')->default(0);
            $table->decimal('base_semestral', 10, 2)->default(0);
            $table->decimal('monto_proporcional', 10, 2)->default(0);
            $table->decimal('monto_minimo', 10, 2)->nullable();
            $table->decimal('monto_final', 10, 2)->default(0);
            $table->decimal('aporte_essalud', 10, 2)->default(0);
            $table->uuid('registrado_por')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'anio', 'periodo'], 'g_employee_anio_periodo_unique');
            $table->index(['anio', 'periodo'], 'g_anio_periodo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gratificaciones');
    }
};
