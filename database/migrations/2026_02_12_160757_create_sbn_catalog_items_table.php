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
        Schema::create('sbn_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 8)->unique()->index();
            $table->string('denominacion', 500);
            $table->string('grupo_generico', 100)->nullable();
            $table->string('clase', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbn_catalog_items');
    }
};
