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
        Schema::create('occurrences', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora');
            $table->enum('turno', ['Mañana', 'Tarde', 'Noche']);
            $table->foreignId('vigilante_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipo', ['Incidente', 'Emergencia', 'Rutina', 'Aviso', 'Robo', 'Otros'])->default('Rutina');
            $table->text('descripcion');
            $table->text('acciones')->nullable();
            $table->string('evidencia_url', 500)->nullable();
            $table->enum('estado', ['Pendiente', 'Aprobado', 'Cerrado'])->default('Pendiente');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['fecha', 'turno']);
            $table->index('tipo');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};
