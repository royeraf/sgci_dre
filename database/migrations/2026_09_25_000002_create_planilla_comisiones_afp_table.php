<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_comisiones_afp', function (Blueprint $table) {
            $table->id();
            $table->date('mes');
            $table->uuid('regimen_pensionario_id');
            $table->decimal('comision_flujo', 6, 4)->default(0.0000);
            $table->decimal('comision_saldo', 6, 4)->default(0.0000);
            $table->timestamps();
            $table->unique(['mes', 'regimen_pensionario_id'], 'comisiones_afp_mes_regimen_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_comisiones_afp');
    }
};
