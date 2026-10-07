<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Días a pagar del empleado en el periodo (base 30). Se reduce cuando el
     * contrato empieza o termina a mitad de mes; 30 = mes completo.
     */
    public function up(): void
    {
        Schema::table('planilla_detalles', function (Blueprint $table) {
            $table->unsignedTinyInteger('dias_pagados')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('planilla_detalles', function (Blueprint $table) {
            $table->dropColumn('dias_pagados');
        });
    }
};
