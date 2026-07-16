<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_inscripciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evento_id');
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('tipo_documento', 20)->default('DNI');
            $table->string('numero_documento', 20);
            $table->string('correo', 150);
            $table->string('celular', 20);
            $table->string('institucion', 150)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->timestamps();

            $table->foreign('evento_id')
                  ->references('id')->on('utilitario_eventos')
                  ->onDelete('cascade');

            $table->unique(['evento_id', 'numero_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_inscripciones');
    }
};
