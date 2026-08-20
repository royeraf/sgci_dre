<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->string('destino', 250)->nullable()->after('motivo_salida');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropColumn('destino');
        });
    }
};
