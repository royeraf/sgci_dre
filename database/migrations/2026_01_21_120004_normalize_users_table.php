<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Normaliza la tabla users:
     * 1. Agrega columna person_id como FK opcional
     * 2. Agrega columna username
     * 3. Mantiene compatibilidad con usuarios sin persona asociada
     */
    public function up(): void
    {
        // Solo agregar columnas que no existen
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'person_id')) {
                $table->uuid('person_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 100)->nullable()->after('person_id');
            }
            // apellidos e is_active ya existen en la tabla
        });

        // Crear username basado en email para usuarios existentes que no tengan username
        $users = DB::table('users')->whereNull('username')->orWhere('username', '')->get();
        foreach ($users as $user) {
            $username = explode('@', $user->email)[0];
            // Asegurar que el username sea único
            $baseUsername = $username;
            $counter = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $username]);
        }

        // Hacer username único y requerido (si la columna existe)
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 100)->nullable(false)->unique()->change();
            });
        }

        // Agregar FK a people (opcional)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('person_id')
                  ->references('id')
                  ->on('people')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn(['person_id', 'username', 'apellidos', 'is_active']);
        });
    }
};
