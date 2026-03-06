<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->uuid('aprobado_por_jefe')->nullable()->after('comentario_aprobacion');
            $table->timestamp('fecha_aprobacion_jefe')->nullable()->after('aprobado_por_jefe');
            $table->uuid('aprobado_por_rrhh')->nullable()->after('fecha_aprobacion_jefe');
            $table->timestamp('fecha_aprobacion_rrhh')->nullable()->after('aprobado_por_rrhh');

            $table->foreign('aprobado_por_jefe')
                  ->references('id')->on('employees')
                  ->onDelete('set null');
            $table->foreign('aprobado_por_rrhh')
                  ->references('id')->on('employees')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropForeign(['aprobado_por_jefe']);
            $table->dropForeign(['aprobado_por_rrhh']);
            $table->dropColumn(['aprobado_por_jefe', 'fecha_aprobacion_jefe', 'aprobado_por_rrhh', 'fecha_aprobacion_rrhh']);
        });
    }
};
