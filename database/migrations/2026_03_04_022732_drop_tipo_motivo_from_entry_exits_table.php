<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop tipo_motivo (now derived from the reason)
        // Note: entry_exit_reason_id stays nullable to preserve existing records
        if (Schema::hasColumn('entry_exits', 'tipo_motivo')) {
            Schema::table('entry_exits', function (Blueprint $table) {
                $table->dropColumn('tipo_motivo');
            });
        }
    }

    public function down(): void
    {
        Schema::table('entry_exits', function (Blueprint $table) {
            $table->string('tipo_motivo', 20)->nullable()->after('entry_exit_reason_id');
            $table->uuid('entry_exit_reason_id')->nullable()->change();
        });
    }
};
