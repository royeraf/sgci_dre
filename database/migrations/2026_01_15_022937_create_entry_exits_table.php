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
        Schema::create('entry_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('dni', 8);
            $table->string('nombre_personal', 200);
            $table->time('hora_salida');
            $table->time('hora_retorno')->nullable();
            $table->text('motivo');
            $table->string('papeleta', 10);
            $table->enum('turno', ['Mañana', 'Tarde', 'Noche']);
            $table->string('regimen', 50)->nullable();
            $table->date('fecha');
            $table->foreignId('registrado_por')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['fecha', 'turno']);
            $table->index('dni');
            $table->index('papeleta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_exits');
    }
};
