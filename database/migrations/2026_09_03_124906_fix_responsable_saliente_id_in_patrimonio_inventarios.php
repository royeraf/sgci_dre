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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE patrimonio_inventarios MODIFY COLUMN responsable_saliente_id CHAR(36) NULL");
        Schema::table('patrimonio_inventarios', function (Blueprint $table) {
            $table->foreign('responsable_saliente_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrimonio_inventarios', function (Blueprint $table) {
            $table->dropForeign(['responsable_saliente_id']);
        });
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE patrimonio_inventarios MODIFY COLUMN responsable_saliente_id BIGINT UNSIGNED NULL");
    }
};
