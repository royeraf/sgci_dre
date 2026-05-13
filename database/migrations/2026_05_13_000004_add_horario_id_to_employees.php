<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('horario_id')->nullable()->after('contract_type_id');
        });

        // Asignar horario general a todos los empleados activos por defecto
        $horarioId = DB::table('horarios')->where('nombre', 'Horario General DRE')->value('id');
        if ($horarioId) {
            DB::table('employees')->where('estado', 'ACTIVO')->update(['horario_id' => $horarioId]);
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('horario_id');
        });
    }
};
