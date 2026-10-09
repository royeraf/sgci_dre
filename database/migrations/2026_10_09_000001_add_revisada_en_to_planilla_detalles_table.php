<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Momento en que el trabajador confirmó la revisión de su boleta desde el
     * portal público por DNI. NULL = pendiente de revisión. Al regenerar la
     * planilla los detalles se borran y recrean (`PlanillaGenerador::limpiarDetalle`),
     * por lo que la confirmación se resetea sola si el cálculo cambia.
     */
    public function up(): void
    {
        Schema::table('planilla_detalles', function (Blueprint $table) {
            $table->timestamp('revisada_en')->nullable()->after('neto_pagar');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_detalles', function (Blueprint $table) {
            $table->dropColumn('revisada_en');
        });
    }
};
