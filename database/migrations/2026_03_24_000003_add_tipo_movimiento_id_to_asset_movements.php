<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->foreignId('tipo_movimiento_id')
                ->nullable()
                ->after('tipo')
                ->constrained('asset_movement_types')
                ->nullOnDelete();
        });

        // Migrate existing tipo string values to the new FK
        DB::statement("
            UPDATE asset_movements am
            INNER JOIN asset_movement_types amt ON amt.codigo = am.tipo
            SET am.tipo_movimiento_id = amt.id
        ");

        // Make tipo nullable for backward compatibility
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->string('tipo', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('asset_movements', function (Blueprint $table) {
            $table->dropForeign(['tipo_movimiento_id']);
            $table->dropColumn('tipo_movimiento_id');
            $table->string('tipo', 50)->nullable(false)->change();
        });
    }
};
