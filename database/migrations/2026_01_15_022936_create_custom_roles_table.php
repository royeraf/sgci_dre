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
        Schema::create('custom_roles', function (Blueprint $table) {
            $table->string('rol_id', 10)->primary();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('nivel_acceso')->default(1);
            $table->json('permisos_json')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Add rol_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('dni', 8)->unique()->after('id');
            $table->string('titulo', 20)->nullable()->after('name');
            $table->string('apellidos', 100)->nullable()->after('name');
            $table->string('cargo', 100)->nullable()->after('email');
            $table->string('area', 100)->nullable()->after('cargo');
            $table->string('telefono', 20)->nullable()->after('area');
            $table->string('rol_id', 10)->nullable()->after('telefono');
            $table->boolean('is_active')->default(true)->after('rol_id');
            $table->timestamp('ultimo_acceso')->nullable()->after('is_active');

            $table->foreign('rol_id')->references('rol_id')->on('custom_roles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rol_id']);
            $table->dropColumn(['dni', 'titulo', 'apellidos', 'cargo', 'area', 'telefono', 'rol_id', 'is_active', 'ultimo_acceso']);
        });

        Schema::dropIfExists('custom_roles');
    }
};
