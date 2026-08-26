<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DigitalCertificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'signer_dni', 'vault_path', 'salt', 'pin_hash', 'certificate_subject',
        'certificate_issuer', 'certificate_serial', 'certificate_thumbprint',
        'valid_from', 'valid_to', 'is_active', 'created_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['vault_path', 'salt', 'pin_hash'];
}
