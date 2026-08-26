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

    public function scopeVigente($query)
    {
        return $query->where('is_active', true)->where('valid_to', '>', now());
    }

    public static function activeForDni(?string $dni): ?self
    {
        if (!$dni) {
            return null;
        }

        return static::where('signer_dni', $dni)->vigente()->first();
    }
}
