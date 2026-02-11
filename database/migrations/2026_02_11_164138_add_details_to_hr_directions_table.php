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
        Schema::table('hr_directions', function (Blueprint $table) {
            $table->string('codigo')->nullable()->after('nombre');
            $table->string('telefono_interno')->nullable()->after('descripcion');
            $table->string('ubicacion')->nullable()->after('telefono_interno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_directions', function (Blueprint $table) {
            $table->dropColumn(['codigo', 'telefono_interno', 'ubicacion']);
        });
    }
};
