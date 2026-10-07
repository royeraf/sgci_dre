<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas de concepto de un detalle de planilla (snapshot por concepto).
     */
    public function up(): void
    {
        Schema::create('planilla_detalle_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('detalle_id');
            $table->uuid('concepto_id')->nullable();
            $table->string('tipo');
            $table->string('descripcion');
            $table->decimal('base_calculo', 10, 2)->default(0);
            $table->decimal('porcentaje', 8, 5)->nullable();
            $table->decimal('monto', 10, 2)->default(0);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index('detalle_id');
            $table->index('concepto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_detalle_items');
    }
};
