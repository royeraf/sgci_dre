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
        Schema::create('external_visits', function (Blueprint $table) {
            $table->id();
            $table->string('dni', 8);
            $table->string('nombres', 200);
            $table->time('hora_ingreso');
            $table->time('hora_salida')->nullable();
            $table->text('motivo')->nullable();
            $table->string('area', 100)->nullable(); // Área u oficina
            $table->string('a_quien_visita', 200)->nullable(); // Persona a quien visita
            $table->date('fecha');
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['fecha']);
            $table->index('dni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_visits');
    }
};
