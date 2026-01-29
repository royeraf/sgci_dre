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
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            
            $table->string('tipo'); // Asignación, Devolución, etc.
            $table->date('fecha');
            
            $table->string('ubicacion_actual')->nullable();
            
            $table->string('responsable_nombre')->nullable(); // Snapshot of name
            $table->foreignId('responsable_id')->nullable()->constrained('asset_responsibles')->nullOnDelete();
            
            $table->string('modalidad_responsable')->nullable();
            
            // $table->foreignId('inventariador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('inventariador_id')->nullable()->index();
            
            $table->string('documento_id')->nullable(); // Could be doc reference
            $table->text('observaciones')->nullable();
            $table->string('estado')->nullable(); // Estado del bien en ese momento (BUENO, MALO)
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
