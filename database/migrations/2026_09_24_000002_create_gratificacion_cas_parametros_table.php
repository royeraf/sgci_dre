<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gratificacion_cas_parametros', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio_fiscal')->unique();
            $table->decimal('porcentaje', 5, 4);
            $table->decimal('monto_minimo', 10, 2)->nullable();
            $table->date('fecha_vigencia_norma')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gratificacion_cas_parametros');
    }
};
