<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleCommissionSignature extends Model
{
    use HasUuids;

    protected $fillable = [
        'vehicle_commission_id', 'signer_employee_id', 'digital_certificate_id',
        'signer_role', 'signer_dni', 'signer_name', 'certificate_thumbprint',
        'signed_document_path', 'document_sha256', 'signed_at', 'document_revision',
    ];

    protected $casts = ['signed_at' => 'datetime'];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(VehicleCommission::class, 'vehicle_commission_id');
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
