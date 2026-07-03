<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->unsignedInteger('numero')->nullable()->after('id');
            $table->unsignedSmallInteger('anio')->nullable()->after('numero');
            $table->string('solicitante')->nullable()->after('dependencia');
            $table->string('referencia')->nullable()->after('lugar');
            $table->string('funcionario_autoriza')->nullable()->after('chofer');
            $table->string('km_salida')->nullable()->after('hora_salida');
            $table->string('km_retorno')->nullable()->after('hora_regreso');
            $table->string('combustible')->nullable()->after('km_retorno');
            $table->string('pnro')->nullable()->after('combustible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn([
                'numero',
                'anio',
                'solicitante',
                'referencia',
                'funcionario_autoriza',
                'km_salida',
                'km_retorno',
                'combustible',
                'pnro',
            ]);
        });
    }
};
