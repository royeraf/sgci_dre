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
        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('dni', 8);
            $table->string('tipo_licencia');
            $table->text('motivo')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_solicitados');
            $table->string('estado')->default('APROBADO'); 
            $table->text('observaciones')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
