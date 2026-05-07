<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zkteco_user_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->integer('zk_user_id');            // ID numérico en el reloj
            $table->char('employee_id', 36);           // UUID del empleado
            $table->string('zk_user_name', 100)->nullable();
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('zkteco_devices')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['device_id', 'zk_user_id']);
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_user_mapping');
    }
};
