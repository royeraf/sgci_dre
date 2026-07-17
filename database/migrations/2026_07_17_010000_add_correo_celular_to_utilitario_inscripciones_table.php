<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->string('correo', 150)->nullable()->after('genero');
            $table->string('celular', 20)->nullable()->after('correo');
        });

        // El dato original que la persona escribió al inscribirse ya no existe (se
        // sobrescribía en people.email/telefono), así que se recupera lo mejor posible
        // desde la persona vinculada como punto de partida.
        DB::statement(
            'UPDATE utilitario_inscripciones ui
             INNER JOIN people p ON p.id = ui.person_id
             SET ui.correo = p.email, ui.celular = p.telefono
             WHERE ui.correo IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->dropColumn(['correo', 'celular']);
        });
    }
};
