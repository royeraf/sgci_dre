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
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropColumn('solicitante');
            $table->foreignUuid('solicitante_employee_id')->nullable()->after('dependencia')
                ->constrained('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropForeign(['solicitante_employee_id']);
            $table->dropColumn('solicitante_employee_id');
            $table->string('solicitante')->nullable()->after('dependencia');
        });
    }
};
