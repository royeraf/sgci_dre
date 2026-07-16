<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->string('genero', 20)->nullable()->after('apellidos');
            $table->uuid('direction_id')->nullable()->after('institucion');
        });
    }

    public function down(): void
    {
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->dropColumn(['genero', 'direction_id']);
        });
    }
};
