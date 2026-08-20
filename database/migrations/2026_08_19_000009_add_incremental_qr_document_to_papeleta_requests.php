<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            // Only records created after this deployment use the certified
            // AcroForm layout. Existing signed documents remain immutable.
            $table->boolean('qr_form_enabled')->default(false)->after('qr_token');
            $table->string('executed_document_path')->nullable()->after('destino_firmado_at');
        });
    }

    public function down(): void
    {
        Schema::table('papeleta_requests', function (Blueprint $table) {
            $table->dropColumn(['qr_form_enabled', 'executed_document_path']);
        });
    }
};
