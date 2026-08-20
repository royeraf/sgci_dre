<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->string('ambito_destino', 20)->nullable()->after('lugar');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn('ambito_destino');
        });
    }
};
