<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_regimenes_pensionarios', function (Blueprint $table) {
            $table->boolean('es_reja')->default(false)->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_regimenes_pensionarios', function (Blueprint $table) {
            $table->dropColumn('es_reja');
        });
    }
};
