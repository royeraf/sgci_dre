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
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn('funcionario_autoriza');

            $table->foreignUuid('autorizado_por')->nullable()->after('conductor_employee_id')
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('fecha_autorizacion')->nullable()->after('autorizado_por');
            $table->text('comentario_autorizacion')->nullable()->after('fecha_autorizacion');
            $table->timestamp('fecha_confirmacion_conductor')->nullable()->after('comentario_autorizacion');

            $table->index(['estado', 'dia']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * vehicle_commissions es una tabla MyISAM: constrained() en up() no crea una
     * FK real (MyISAM no las soporta), solo el índice sobre la columna, y por
     * la misma razón dropForeign() no encuentra ningún constraint que retirar.
     * Cada paso va en su propio Schema::table() para que, si uno ya no aplica
     * (p.ej. el índice ya fue retirado a mano), el resto igual se ejecute.
     */
    public function down(): void
    {
        try {
            Schema::table('vehicle_commissions', function (Blueprint $table) {
                $table->dropIndex(['estado', 'dia']);
            });
        } catch (\Throwable $e) {
            // El índice ya no existe (p.ej. rollback repetido); no hay nada que hacer.
        }

        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn([
                'autorizado_por',
                'fecha_autorizacion',
                'comentario_autorizacion',
                'fecha_confirmacion_conductor',
            ]);
        });

        if (!Schema::hasColumn('vehicle_commissions', 'funcionario_autoriza')) {
            Schema::table('vehicle_commissions', function (Blueprint $table) {
                $table->string('funcionario_autoriza')->nullable()->after('conductor_employee_id');
            });
        }
    }
};
