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
        Schema::create('driver_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id')->unique();
            $table->string('numero', 20);
            $table->string('categoria', 10);
            $table->date('fecha_vencimiento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_licenses');
    }
};
