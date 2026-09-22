<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de planilla específicos del empleado.
     *
     * Corresponde a "DATOS DEL TRABAJADOR" de la boleta:
     * sistema de pensiones, CUSPP y cuenta de abono (CTA AHORRO DE DEPÓSITO).
     */
    public function up(): void
    {
        Schema::create('employee_payroll_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->unique();
            $table->uuid('regimen_pensionario_id')->nullable();
            $table->string('cuspp')->nullable();
            $table->string('banco')->nullable();
            $table->string('cuenta_ahorro')->nullable();
            $table->timestamps();

            $table->index('regimen_pensionario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_profiles');
    }
};
