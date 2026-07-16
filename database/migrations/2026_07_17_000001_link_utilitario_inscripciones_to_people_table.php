<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * people usa el motor MyISAM (igual que external_visits, employees, users), que no
     * soporta constraints FK reales. person_id queda como referencia lógica indexada,
     * igual que external_visits.person_id hoy en producción.
     */
    public function up(): void
    {
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->uuid('person_id')->nullable()->after('evento_id');
        });

        // El unique (evento_id, numero_documento) sostiene el índice que exige la FK de
        // evento_id -> utilitario_eventos; hay que crear el nuevo unique ANTES de dropear
        // el viejo para no dejar la FK sin índice de soporte.
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->unique(['evento_id', 'person_id']);
        });

        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->dropUnique(['evento_id', 'numero_documento']);
            $table->dropColumn(['nombres', 'apellidos', 'correo', 'celular', 'numero_documento', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->string('nombres', 100)->after('evento_id');
            $table->string('apellidos', 100)->after('nombres');
            $table->string('tipo_documento', 20)->default('DNI')->after('apellidos');
            $table->string('numero_documento', 20)->after('tipo_documento');
            $table->string('correo', 150)->after('numero_documento');
            $table->string('celular', 20)->after('correo');
        });

        // De nuevo: crear el unique viejo antes de dropear el nuevo, para no dejar
        // la FK de evento_id sin índice de soporte en ningún momento.
        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->unique(['evento_id', 'numero_documento']);
        });

        Schema::table('utilitario_inscripciones', function (Blueprint $table) {
            $table->dropUnique(['evento_id', 'person_id']);
            $table->dropColumn('person_id');
        });
    }
};
