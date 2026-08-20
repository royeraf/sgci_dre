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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
            $table->string('model_id', 36)->nullable()->change();
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
            $table->unsignedBigInteger('model_id')->nullable()->change();
            $table->index(['model_type', 'model_id']);
        });
    }
};