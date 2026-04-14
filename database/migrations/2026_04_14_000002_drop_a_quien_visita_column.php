<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_visits', function (Blueprint $table) {
            $fks = collect(\DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'external_visits' AND COLUMN_NAME = 'a_quien_visita' AND REFERENCED_TABLE_NAME IS NOT NULL"));
            if ($fks->isNotEmpty()) {
                $table->dropForeign(['a_quien_visita']);
            }
            if (\Schema::hasColumn('external_visits', 'a_quien_visita')) {
                $table->dropColumn('a_quien_visita');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_visits', function (Blueprint $table) {
            $table->char('a_quien_visita', 36)->nullable();
        });
    }
};
