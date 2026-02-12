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
        Schema::create('patrimonio_assets', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('codigo_patrimonial', 20)->index();
            $table->string('codigo_interno', 10)->nullable();
            $table->string('codigo_activo', 20)->nullable();

            // Descripción
            $table->string('denominacion', 500)->nullable();
            $table->text('descripcion')->nullable();
            $table->text('caracteristicas')->nullable();
            $table->text('observaciones')->nullable();

            // Clasificación técnica
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('nro_serie', 100)->nullable();
            $table->string('medidas', 100)->nullable();

            // Estado
            $table->string('estado_conserv', 50)->nullable();
            $table->string('estado_desc', 50)->nullable();

            // Responsable y ubicación
            $table->string('responsable_final', 200)->nullable();
            $table->string('empleado_final', 20)->nullable();
            $table->string('oficina', 200)->nullable();

            // Fechas
            $table->date('fecha_compra')->nullable();
            $table->date('fecha_alta')->nullable();

            // Valores financieros
            $table->decimal('valor_compra', 15, 6)->nullable();
            $table->decimal('valor_inicial', 15, 2)->nullable();
            $table->decimal('valor_deprec', 15, 2)->nullable();

            // Código de barras
            $table->string('codigo_barra', 50)->nullable()->index();

            // Metadatos de importación
            $table->string('archivo_origen')->nullable();
            $table->dateTime('fecha_importacion');
            $table->unsignedBigInteger('importado_por')->nullable();
            $table->string('lote_importacion', 50)->index();

            // Sincronización con tabla assets
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->boolean('sincronizado')->default(false);
            $table->dateTime('fecha_sincronizacion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patrimonio_assets');
    }
};
