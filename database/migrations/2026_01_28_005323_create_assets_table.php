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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_patrimonio')->nullable();
            $table->string('codigo_interno')->nullable();
            $table->string('codigo_completo')->nullable()->index(); // For searches
            $table->text('detalle_bien')->nullable();
            $table->string('descripcion')->nullable();
            
            $table->foreignId('categoria_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('dimension')->nullable();
            $table->string('color')->nullable();
            
            $table->date('fecha_adquisicion')->nullable();
            $table->date('fecha_asignacion')->nullable();
            $table->date('fecha_retiro')->nullable();
            
            $table->string('tipo_origen')->nullable(); // SIGA, SOBRANTE, etc
            $table->string('estado')->default('BUENO');
            
            $table->foreignId('responsable_id')->nullable()->constrained('asset_responsibles')->nullOnDelete();
            
            $table->string('tipo_modalidad')->nullable();
            
            // $table->foreignId('inventariador_id')->nullable()->constrained('users')->nullOnDelete(); 
            // Constraint disabled due to errno 150 potential mismatch with users table
            $table->unsignedBigInteger('inventariador_id')->nullable()->index();
            
            $table->text('observacion')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->string('codigo_patrimonial')->nullable();
            $table->string('oficina')->nullable();
            $table->string('fuente')->nullable();
            $table->string('tipo_registro')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
