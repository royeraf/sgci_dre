<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de la entidad emisora: identificación fiscal, razón social y
     * domicilio. Es una tabla de una sola fila (singleton) que alimenta el
     * encabezado de la boleta de pago.
     */
    public function up(): void
    {
        Schema::create('dre_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('ruc', 20)->unique();
            $table->string('razon_social', 191);
            $table->string('nombre_abreviado', 60)->nullable();
            $table->string('direccion', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dre_configuracion');
    }
};
