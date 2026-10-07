<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de regímenes pensionarios (ONP y AFP) con sus tasas.
     *
     * Tasas observadas en la planilla CAS - Sede (Planilla 0042):
     *  - ONP (Ley 19990): aporte obligatorio 13%
     *  - AFP: aporte obligatorio 10%, prima de seguro 1.37%,
     *         comisión fija/mixta variable según la AFP.
     */
    public function up(): void
    {
        Schema::create('planilla_regimenes_pensionarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre')->unique();
            $table->enum('tipo', ['ONP', 'AFP'])->default('AFP');
            $table->decimal('aporte_obligatorio', 8, 5)->default(0.10000);
            $table->decimal('prima_seguro', 8, 5)->default(0.01370);
            $table->decimal('comision_flujo', 8, 5)->nullable();
            $table->decimal('comision_mixta', 8, 5)->nullable();
            $table->decimal('comision_fija', 8, 5)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_regimenes_pensionarios');
    }
};
