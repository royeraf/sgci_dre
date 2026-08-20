<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PapeletaRequestSignature extends Model
{
    use HasUuids;

    protected $fillable = [
        'papeleta_request_id', 'signer_employee_id', 'digital_certificate_id',
        'signer_role', 'signer_dni', 'signer_name', 'certificate_thumbprint',
        'signed_document_path', 'document_sha256', 'signed_at',
    ];

    protected $casts = ['signed_at' => 'datetime'];

    public function papeleta(): BelongsTo
    {
        return $this->belongsTo(PapeletaRequest::class, 'papeleta_request_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'signer_employee_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificate::class, 'digital_certificate_id');
    }
}
