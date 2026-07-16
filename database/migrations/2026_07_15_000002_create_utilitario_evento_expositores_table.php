<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilitario_evento_expositores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evento_id');
            $table->string('nombre', 150);
            $table->string('entidad', 150)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->foreign('evento_id')
                  ->references('id')->on('utilitario_eventos')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utilitario_evento_expositores');
    }
};
