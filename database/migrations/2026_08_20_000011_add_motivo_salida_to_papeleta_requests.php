<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->string('motivo_salida', 32)->nullable()->after('entry_exit_reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropColumn('motivo_salida');
        });
    }
};
