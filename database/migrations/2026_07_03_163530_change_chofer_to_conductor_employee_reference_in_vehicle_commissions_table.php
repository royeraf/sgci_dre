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
            $table->dropColumn('chofer');
            $table->foreignUuid('conductor_employee_id')->nullable()->after('vehicle_id')
                ->constrained('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_commissions', function (Blueprint $table) {
            $table->dropForeign(['conductor_employee_id']);
            $table->dropColumn('conductor_employee_id');
            $table->string('chofer')->nullable()->after('vehicle_id');
        });
    }
};
