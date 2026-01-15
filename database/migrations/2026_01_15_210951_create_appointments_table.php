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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 8);
            $table->string('oficina');
            $table->string('apellidos');
            $table->string('nombres');
            $table->date('fecha');
            $table->string('hora');
            $table->string('celular');
            $table->string('correo');
            $table->text('asunto');
            $table->enum('estado', ['PENDIENTE', 'ATENDIDO', 'CANCELADO'])->default('PENDIENTE');
            $table->json('historial')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
