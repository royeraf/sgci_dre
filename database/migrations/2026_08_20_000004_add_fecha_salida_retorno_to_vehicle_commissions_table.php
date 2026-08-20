<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->date('fecha_salida')->nullable()->after('fecha_confirmacion_conductor');
            $table->date('fecha_retorno')->nullable()->after('hora_salida');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn(['fecha_salida', 'fecha_retorno']);
        });
    }
};
