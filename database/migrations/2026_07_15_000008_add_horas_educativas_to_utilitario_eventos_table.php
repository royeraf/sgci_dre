<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilitario_eventos', function (Blueprint $table) {
            $table->unsignedInteger('horas_educativas')->nullable()->after('cupo_maximo');
        });
    }

    public function down(): void
    {
        Schema::table('utilitario_eventos', function (Blueprint $table) {
            $table->dropColumn('horas_educativas');
        });
    }
};
