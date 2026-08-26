<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // papeleta_requests es MyISAM (ver 2026_08_17_000007_create_papeleta_request_signatures_table.php),
        // así que no se declara FK hacia employees; la integridad se valida
        // desde storeMiPapeleta(). NULL significa "papeleta legada": se
        // resuelve en vivo por la designación de oficina/dirección, tal
        // como antes de este cambio.
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->uuid('jefe_asignado_id')->nullable()->after('employee_id');
            $table->string('jefe_asignado_dni', 8)->nullable()->after('jefe_asignado_id');
            $table->string('jefe_asignado_nombre', 180)->nullable()->after('jefe_asignado_dni');

            $table->index(['jefe_asignado_id', 'estado'], 'papeleta_requests_jefe_asignado_index');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropIndex('papeleta_requests_jefe_asignado_index');
            $table->dropColumn(['jefe_asignado_id', 'jefe_asignado_dni', 'jefe_asignado_nombre']);
        });
    }
};
