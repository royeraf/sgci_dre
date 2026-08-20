<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sin ->foreign(): las tablas de este módulo son MyISAM y MySQL ignora
        // las restricciones de clave foránea en ese engine (solo crea el índice).
        Schema::create('vehicle_commission_passengers', function (Blueprint $table) {
            $table->uuid('vehicle_commission_id');
            $table->uuid('employee_id');
            $table->unique(['vehicle_commission_id', 'employee_id'], 'vcp_commission_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_commission_passengers');
    }
};
