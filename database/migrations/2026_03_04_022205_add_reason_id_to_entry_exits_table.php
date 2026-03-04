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
        Schema::table('entry_exits', function (Blueprint $table) {
            $table->uuid('entry_exit_reason_id')->nullable()->after('tipo_motivo');
            $table->foreign('entry_exit_reason_id')
                  ->references('id')->on('entry_exit_reasons')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_exits', function (Blueprint $table) {
            $table->dropForeign(['entry_exit_reason_id']);
            $table->dropColumn('entry_exit_reason_id');
        });
    }
};
