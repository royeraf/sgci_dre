<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->uuid('encargatura_id')->nullable()->after('position_id');
            $table->foreign('encargatura_id')->references('id')->on('hr_positions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['encargatura_id']);
            $table->dropColumn('encargatura_id');
        });
    }
};
