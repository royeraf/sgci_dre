<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // vehicle_commissions es MyISAM (ver 2026_08_18_000001_..._vehicle_commissions_table.php):
        // MySQL no permite claves foráneas hacia esa tabla; se conservan índices
        // y la integridad se valida desde los modelos, igual que en
        // papeleta_request_signatures.
        Schema::create('vehicle_commission_signatures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vehicle_commission_id');
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

            $table->unique(['vehicle_commission_id', 'signer_role'], 'vehicle_commission_role_signature_unique');
            $table->index(['vehicle_commission_id', 'signed_at'], 'vehicle_commission_signatures_commission_signed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_commission_signatures');
    }
};
