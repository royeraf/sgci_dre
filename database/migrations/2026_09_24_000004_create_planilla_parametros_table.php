<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros anuales de planilla: base imponible de EsSalud (CAS).
     *
     * Tope = UIT × %tope (art. 6 inc. k del DL 1057: 45% de la UIT vigente).
     * Base mínima = RMV (art. 6 de la Ley 26790).
     */
    public function up(): void
    {
        Schema::create('planilla_parametros', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio')->unique();
            $table->decimal('uit', 8, 2);
            $table->decimal('pct_tope_essalud', 5, 4)->default(0.4500);
            $table->decimal('rmv', 8, 2)->nullable();
            $table->decimal('tasa_essalud', 5, 4)->default(0.0900);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_parametros');
    }
};
