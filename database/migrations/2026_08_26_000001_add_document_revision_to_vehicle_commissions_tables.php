<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A single atomic counter, shared by both revision streams (raw
        // signatures and executed/data-filled copies), replaces comparing
        // them by wall-clock second: signing and refreshing can legitimately
        // happen within the same second (they're often two steps of the
        // same request), which made that comparison tie and pick the wrong
        // "latest" revision.
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->unsignedInteger('document_revision')->default(0)->after('executed_document_path');
            $table->unsignedInteger('executed_document_revision')->nullable()->after('document_revision');
        });

        Schema::table('vehicle_commission_signatures', function (Blueprint $table) {
            $table->unsignedInteger('document_revision')->default(0)->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn(['document_revision', 'executed_document_revision']);
        });

        Schema::table('vehicle_commission_signatures', function (Blueprint $table) {
            $table->dropColumn('document_revision');
        });
    }
};
