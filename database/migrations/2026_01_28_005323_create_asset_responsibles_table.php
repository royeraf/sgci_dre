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
        Schema::create('asset_responsibles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_original');
            $table->string('nombre_normalizado')->nullable();
            $table->string('area')->nullable();
            $table->string('tipo_contrato')->nullable();
            $table->boolean('activo')->default(true);
            $table->uuid('employee_id')->nullable(); // Link to existing Employee system if needed later
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_responsibles');
    }
};
