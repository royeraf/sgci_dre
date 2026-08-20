<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('signer_dni', 8)->unique();
            $table->string('vault_path');
            $table->string('salt', 64);
            $table->string('pin_hash');
            $table->text('certificate_subject');
            $table->text('certificate_issuer');
            $table->string('certificate_serial', 150);
            $table->string('certificate_thumbprint', 64);
            $table->timestamp('valid_from');
            $table->timestamp('valid_to');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_certificates');
    }
};
