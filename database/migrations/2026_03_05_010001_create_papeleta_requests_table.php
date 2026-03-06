<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papeleta_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_papeleta', 10)->unique();
            $table->uuid('employee_id');
            $table->uuid('entry_exit_reason_id');
            $table->text('motivo');
            $table->date('fecha_salida');
            $table->time('hora_salida_estimada');
            $table->time('hora_retorno_estimada')->nullable();
            $table->string('turno', 10)->default('Manana');
            $table->string('estado', 20)->default('PENDIENTE');
            $table->uuid('aprobado_por')->nullable();
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->text('comentario_aprobacion')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');
            $table->foreign('entry_exit_reason_id')
                  ->references('id')->on('entry_exit_reasons')
                  ->onDelete('restrict');
            $table->foreign('aprobado_por')
                  ->references('id')->on('employees')
                  ->onDelete('set null');

            $table->index(['employee_id', 'estado']);
            $table->index(['estado', 'fecha_salida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papeleta_requests');
    }
};
