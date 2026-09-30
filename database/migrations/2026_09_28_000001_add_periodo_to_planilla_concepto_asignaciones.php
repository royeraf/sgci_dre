<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_concepto_asignaciones', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio')->nullable()->after('hasta');
            $table->unsignedTinyInteger('mes')->nullable()->after('anio');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_concepto_asignaciones', function (Blueprint $table) {
            $table->dropColumn(['anio', 'mes']);
        });
    }
};
