<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marcas_asistencia', function (Blueprint $table) {
            $table->time('entrada_3')->nullable()->after('salida');
            $table->time('salida_3')->nullable()->after('entrada_3');
            $table->time('entrada_4')->nullable()->after('salida_3');
            $table->time('salida_4')->nullable()->after('entrada_4');
        });
    }

    public function down(): void
    {
        Schema::table('marcas_asistencia', function (Blueprint $table) {
            $table->dropColumn(['entrada_3', 'salida_3', 'entrada_4', 'salida_4']);
        });
    }
};
