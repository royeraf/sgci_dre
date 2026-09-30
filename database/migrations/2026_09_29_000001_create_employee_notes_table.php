<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anotaciones libres por empleado (no dependen del periodo): sirven para
     * dejar detalles pendientes de cara a la próxima planilla.
     */
    public function up(): void
    {
        Schema::create('employee_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->text('texto');
            $table->uuid('registrado_por')->nullable();
            $table->timestamps();

            $table->index('employee_id', 'en_employee_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_notes');
    }
};
