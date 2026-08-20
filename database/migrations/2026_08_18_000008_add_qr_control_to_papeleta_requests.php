<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable()->unique()->after('estado');
            $table->timestamp('salida_real_at')->nullable()->after('hora_retorno_estimada');
            $table->timestamp('retorno_real_at')->nullable()->after('salida_real_at');
            $table->string('destino_firmante_nombre')->nullable()->after('retorno_real_at');
            $table->string('destino_firmante_cargo')->nullable()->after('destino_firmante_nombre');
            $table->string('destino_firma_path')->nullable()->after('destino_firmante_cargo');
            $table->timestamp('destino_firmado_at')->nullable()->after('destino_firma_path');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['qr_token', 'salida_real_at', 'retorno_real_at', 'destino_firmante_nombre', 'destino_firmante_cargo', 'destino_firma_path', 'destino_firmado_at']);
        });
    }
};
