<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_devices', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->default('');
            $table->string('ip', 45);
            $table->integer('puerto')->default(4370);
            $table->integer('comm_key')->default(0);
            $table->enum('protocolo', ['tcp', 'udp'])->default('tcp');
            $table->string('serial_number', 100)->nullable();
            $table->string('firmware', 100)->nullable();
            $table->string('ubicacion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('ultima_sincronizacion')->nullable();
            $table->timestamps();

            $table->unique(['ip', 'puerto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_devices');
    }
};
