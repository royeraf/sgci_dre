<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->string('destino_firmante_dni', 8)->nullable()->after('destino_firmante_nombre');
            $table->decimal('destino_latitude', 10, 7)->nullable()->after('destino_firmado_at');
            $table->decimal('destino_longitude', 10, 7)->nullable()->after('destino_latitude');
            $table->decimal('destino_gps_accuracy_m', 10, 2)->nullable()->after('destino_longitude');
            $table->timestamp('destino_gps_at')->nullable()->after('destino_gps_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropColumn(['destino_firmante_dni', 'destino_latitude', 'destino_longitude', 'destino_gps_accuracy_m', 'destino_gps_at']);
        });
    }
};
