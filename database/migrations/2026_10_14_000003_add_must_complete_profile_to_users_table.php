<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bandera para el segundo paso del primer acceso: tras cambiar la contraseña,
     * si al usuario le faltan datos de contacto (correo/celular) se le obliga a
     * completarlos antes de usar el sistema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'must_complete_profile')) {
                $table->boolean('must_complete_profile')->default(false)->after('must_change_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_complete_profile');
        });
    }
};
