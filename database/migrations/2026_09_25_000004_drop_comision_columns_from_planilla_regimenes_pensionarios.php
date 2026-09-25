<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_regimenes_pensionarios', function (Blueprint $table) {
            $table->dropColumn(['comision_flujo', 'comision_mixta', 'comision_fija']);
        });
    }

    public function down(): void
    {
        Schema::table('planilla_regimenes_pensionarios', function (Blueprint $table) {
            $table->decimal('comision_flujo', 6, 4)->nullable();
            $table->decimal('comision_mixta', 6, 4)->nullable();
            $table->decimal('comision_fija', 6, 4)->nullable();
        });
    }
};
