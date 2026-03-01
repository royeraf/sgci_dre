<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrimonio_inventarios', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('anio')->index();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            // PENDIENTE | EN_PROCESO | CERRADO
            $table->string('estado', 20)->default('PENDIENTE');

            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimonio_inventarios');
    }
};
