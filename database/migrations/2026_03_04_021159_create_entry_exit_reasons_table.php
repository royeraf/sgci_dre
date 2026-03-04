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
        Schema::create('entry_exit_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['comision', 'permiso', 'ambos'])->default('ambos');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_exit_reasons');
    }
};
