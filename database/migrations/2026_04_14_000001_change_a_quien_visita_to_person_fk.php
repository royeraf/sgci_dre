<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Limpiar datos de texto existentes (no son UUIDs válidos)
        DB::table('external_visits')->update(['a_quien_visita' => null]);

        Schema::table('external_visits', function (Blueprint $table) {
            $table->char('a_quien_visita', 36)->nullable()->change();
            $table->foreign('a_quien_visita')
                  ->references('id')
                  ->on('people')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('external_visits', function (Blueprint $table) {
            $table->dropForeign(['a_quien_visita']);
            $table->string('a_quien_visita', 200)->nullable()->change();
        });
    }
};
