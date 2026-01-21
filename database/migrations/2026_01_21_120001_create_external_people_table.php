<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla para datos adicionales de personas externas (visitantes)
     * Solo almacena información específica de externos, los datos personales
     * están en la tabla 'people'
     */
    public function up(): void
    {
        Schema::create('external_people', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('person_id');
            $table->string('institucion', 200)->nullable();
            $table->string('cargo_externo', 100)->nullable();
            $table->text('motivo_frecuente')->nullable();
            $table->timestamps();

            $table->foreign('person_id')
                  ->references('id')
                  ->on('people')
                  ->onDelete('cascade');

            $table->index('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_people');
    }
};
