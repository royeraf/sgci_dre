<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normaliza la tabla users para evitar duplicación de datos personales:
     * - name, email, dni pasan a ser nullable (vienen de people cuando person_id está presente)
     * - person_id se vuelve único (una persona → un usuario del sistema)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 191)->nullable()->change();
            $table->string('email', 191)->nullable()->change();
            $table->string('dni', 8)->nullable()->change();
            // Un usuario por persona
            $table->unique('person_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['person_id']);
            $table->string('name', 191)->nullable(false)->change();
            $table->string('email', 191)->nullable(false)->change();
            $table->string('dni', 8)->nullable(false)->change();
        });
    }
};
