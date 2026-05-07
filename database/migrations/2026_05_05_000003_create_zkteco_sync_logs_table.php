<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->enum('tipo', ['manual', 'automatica', 'live'])->default('manual');
            $table->integer('marcas_nuevas')->default(0);
            $table->integer('marcas_duplicadas')->default(0);
            $table->integer('marcas_sin_mapeo')->default(0);
            $table->text('error')->nullable();
            $table->dateTime('ejecutado_en');
            $table->integer('duracion_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('device_id')->references('id')->on('zkteco_devices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_sync_logs');
    }
};
