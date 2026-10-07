<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_parametros_afp', function (Blueprint $table) {
            $table->id();
            $table->date('mes')->unique();
            $table->decimal('aporte_obligatorio', 6, 4)->default(0.1000);
            $table->decimal('prima_seguro', 6, 4)->default(0.0137);
            $table->decimal('remuneracion_maxima_asegurable', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_parametros_afp');
    }
};
