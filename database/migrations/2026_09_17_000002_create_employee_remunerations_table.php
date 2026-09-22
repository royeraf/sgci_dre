<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remuneración base del empleado con vigencia.
     *
     * Corresponde al concepto "Remuneraciones DL 1057" de la boleta.
     * Se guarda con rango [desde, hasta] para poder recalcular planillas
     * históricas sin que un aumento contamine periodos anteriores.
     *
     * Nota: la BD de producción usa MyISAM en las tablas existentes, por lo
     * que no se declaran foreign keys a `employees`/`users`; se usan columnas
     * indexadas (mismo criterio que el resto del sistema).
     */
    public function up(): void
    {
        Schema::create('employee_remunerations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->decimal('monto', 10, 2);
            $table->string('tipo')->default('BASICA');
            $table->date('desde');
            $table->date('hasta')->nullable();
            $table->string('motivo')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'desde']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_remunerations');
    }
};
