<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // employees es MyISAM (ver 2026_08_17_000007_create_papeleta_request_signatures_table.php),
        // así que no se declara FK hacia esa tabla; la integridad se valida
        // desde el modelo/controlador.
        Schema::create('hr_jefe_suplentes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('suplentable'); // suplentable_type, suplentable_id (HrOffice | HrDirection)
            $table->uuid('employee_id');
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('observacion')->nullable();
            $table->timestamps();

            $table->unique(['suplentable_type', 'suplentable_id', 'employee_id'], 'hr_jefe_suplentes_unidad_empleado_unique');
            $table->index(['employee_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_jefe_suplentes');
    }
};
