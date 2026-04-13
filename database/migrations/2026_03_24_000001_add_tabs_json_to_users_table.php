<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('tabs_json')->nullable()->after('modulos_json')
                ->comment('Per-user tab restrictions per module. null = all tabs allowed. e.g. {"patrimonio":["list","reports"]}');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tabs_json');
        });
    }
};
