<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de conceptos de planilla.
     *
     * Los tres tipos mapean 1:1 con las secciones de la boleta de pago:
     *  - INGRESO    → REMUNERACIONES
     *  - DESCUENTO  → RETENCIONES / DESCUENTOS
     *  - APORTACION → APORTACIONES DEL EMPLEADOR (Essalud)
     *
     * Los montos DS (311-2022, 313-2023, etc.) cambian por norma, por eso
     * son datos del catálogo y no columnas fijas.
     */
    public function up(): void
    {
        Schema::create('planilla_conceptos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->enum('tipo', ['INGRESO', 'DESCUENTO', 'APORTACION']);
            $table->string('categoria')->nullable();
            $table->boolean('afecto_renta5')->default(false);
            $table->boolean('afecto_essalud')->default(true);
            $table->boolean('afecto_onp')->default(true);
            $table->boolean('afecto_afp')->default(true);
            $table->boolean('es_porcentaje')->default(false);
            $table->decimal('valor', 12, 5)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_conceptos');
    }
};
