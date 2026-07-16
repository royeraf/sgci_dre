<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilitario_eventos', function (Blueprint $table) {
            $table->string('color_primario', 7)->default('#d97706')->after('horas_educativas');
            $table->string('imagen_fondo')->nullable()->after('color_primario');
        });
    }

    public function down(): void
    {
        Schema::table('utilitario_eventos', function (Blueprint $table) {
            $table->dropColumn(['color_primario', 'imagen_fondo']);
        });
    }
};
