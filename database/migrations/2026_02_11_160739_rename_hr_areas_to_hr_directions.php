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
        // 1. Renombrar la tabla principal
        Schema::rename('hr_areas', 'hr_directions');

        // 2. Renombrar columnas en tablas relacionadas
        Schema::table('hr_offices', function (Blueprint $table) {
            $table->renameColumn('area_id', 'direction_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('area_id', 'direction_id');
        });

        Schema::table('external_visits', function (Blueprint $table) {
            $table->renameColumn('area_id', 'direction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_visits', function (Blueprint $table) {
            $table->renameColumn('direction_id', 'area_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('direction_id', 'area_id');
        });

        Schema::table('hr_offices', function (Blueprint $table) {
            $table->renameColumn('direction_id', 'area_id');
        });

        Schema::rename('hr_directions', 'hr_areas');
    }
};
