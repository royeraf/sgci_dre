<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detalle de planilla: una fila por empleado en un periodo.
     * Guarda snapshots de la remuneración y los totales calculados.
     */
    public function up(): void
    {
        Schema::create('planilla_detalles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('periodo_id');
            $table->uuid('employee_id');
            $table->decimal('remuneracion_base', 10, 2)->default(0);
            $table->decimal('total_ingresos', 10, 2)->default(0);
            $table->decimal('total_descuentos', 10, 2)->default(0);
            $table->decimal('total_aportaciones', 10, 2)->default(0);
            $table->decimal('neto_pagar', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['periodo_id', 'employee_id'], 'pd_periodo_employee_unique');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_detalles');
    }
};
