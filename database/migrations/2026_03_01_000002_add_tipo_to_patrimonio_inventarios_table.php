<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patrimonio_inventarios', function (Blueprint $table) {
            // ANUAL | ROTACION | EXTRAORDINARIO
            $table->string('tipo', 20)->default('ANUAL')->after('anio');

            // Empleado saliente (solo para tipo ROTACION)
            $table->foreignId('responsable_saliente_id')
                ->nullable()
                ->after('cerrado_por')
                ->constrained('employees')
                ->nullOnDelete();
        });

        Schema::table('patrimonio_inventario_items', function (Blueprint $table) {
            // Responsable que tenía el bien antes de la reasignación
            $table->foreignId('responsable_anterior_id')
                ->nullable()
                ->after('responsable_id')
                ->constrained('asset_responsibles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patrimonio_inventarios', function (Blueprint $table) {
            $table->dropForeign(['responsable_saliente_id']);
            $table->dropColumn(['tipo', 'responsable_saliente_id']);
        });

        Schema::table('patrimonio_inventario_items', function (Blueprint $table) {
            $table->dropForeign(['responsable_anterior_id']);
            $table->dropColumn('responsable_anterior_id');
        });
    }
};
