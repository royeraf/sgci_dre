<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asignación de un concepto a un empleado o a un régimen contractual.
     *
     * Si employee_id está presente, aplica solo a ese empleado.
     * Si contract_type_id está presente, aplica a todos los empleados de
     * ese régimen (p. ej. todos los CAS reciben DS 311-2022).
     * El monto/porcentaje aquí definido sobreescribe el valor del catálogo.
     */
    public function up(): void
    {
        Schema::create('planilla_concepto_asignaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('concepto_id');
            $table->uuid('employee_id')->nullable();
            $table->uuid('contract_type_id')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->decimal('porcentaje', 8, 5)->nullable();
            $table->date('desde')->nullable();
            $table->date('hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['concepto_id', 'employee_id'], 'pca_concepto_employee_idx');
            $table->index(['concepto_id', 'contract_type_id'], 'pca_concepto_contract_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_concepto_asignaciones');
    }
};
