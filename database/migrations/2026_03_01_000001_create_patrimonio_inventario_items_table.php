<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonio_inventario_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventario_id')->constrained('patrimonio_inventarios')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();

            // Datos verificados en campo
            $table->foreignId('estado_id')->nullable()->constrained('asset_states')->nullOnDelete();
            $table->foreignId('oficina_id')->nullable()->constrained('hr_offices')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('asset_responsibles')->nullOnDelete();

            $table->text('observaciones')->nullable();

            // Quién y cuándo verificó
            $table->foreignId('inventariador_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_verificacion');

            $table->timestamps();

            // Cada bien aparece una sola vez por inventario
            $table->unique(['inventario_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimonio_inventario_items');
    }
};
