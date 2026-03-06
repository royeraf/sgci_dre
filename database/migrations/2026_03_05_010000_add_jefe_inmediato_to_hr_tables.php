<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_directions', function (Blueprint $table) {
            $table->uuid('jefe_inmediato_id')->nullable()->after('activo');
            $table->foreign('jefe_inmediato_id')
                  ->references('id')->on('employees')
                  ->onDelete('set null');
        });

        Schema::table('hr_offices', function (Blueprint $table) {
            $table->uuid('jefe_inmediato_id')->nullable()->after('activo');
            $table->foreign('jefe_inmediato_id')
                  ->references('id')->on('employees')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('hr_directions', function (Blueprint $table) {
            $table->dropForeign(['jefe_inmediato_id']);
            $table->dropColumn('jefe_inmediato_id');
        });

        Schema::table('hr_offices', function (Blueprint $table) {
            $table->dropForeign(['jefe_inmediato_id']);
            $table->dropColumn('jefe_inmediato_id');
        });
    }
};
