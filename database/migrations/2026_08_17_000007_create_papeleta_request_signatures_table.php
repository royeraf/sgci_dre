<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La base institucional heredada mantiene employees y papeleta_requests
        // en MyISAM. MySQL no permite claves foráneas hacia esas tablas; se
        // conservan índices y la integridad se valida desde los modelos.
        Schema::dropIfExists('papeleta_request_signatures');
        Schema::create('papeleta_request_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('papeleta_request_id');
            $table->uuid('signer_employee_id');
            $table->uuid('digital_certificate_id');
            $table->string('signer_role', 30);
            $table->string('signer_dni', 8);
            $table->string('signer_name');
            $table->string('certificate_thumbprint', 64);
            $table->string('signed_document_path');
            $table->string('document_sha256', 64);
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['papeleta_request_id', 'signer_role'], 'papeleta_role_signature_unique');
            $table->index(['papeleta_request_id', 'signed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papeleta_request_signatures');
    }
};
