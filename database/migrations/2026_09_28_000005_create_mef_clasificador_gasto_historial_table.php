<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mef_clasificador_gasto_historial', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('clasificador_id');
            $table->string('campo', 100);
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->dateTime('fecha_cambio');
            $table->string('fuente', 500)->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();

            $table->index('clasificador_id', 'mef_hist_clasificador_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mef_clasificador_gasto_historial');
    }
};
