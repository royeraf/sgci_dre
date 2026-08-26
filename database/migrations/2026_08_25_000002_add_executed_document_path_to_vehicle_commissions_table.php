<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            // Latest PDF revision after the 3 signatures, with the real
            // departure/return data filled into fields reserved before the
            // first signature. Falls back to the last signature's PDF when
            // still null (nothing executed yet).
            $table->string('executed_document_path')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn('executed_document_path');
        });
    }
};
