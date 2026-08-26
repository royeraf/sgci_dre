<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DigitalCertificate;
use App\Models\Employee;
use Illuminate\Validation\ValidationException;

/**
 * Compartido por los controladores que exigen firma digital (papeletas y
 * autorizaciones de salida vehicular): ambos necesitan resolver el
 * certificado RENIEC vigente de un empleado antes de firmar.
 */
trait ResolvesDigitalCertificate
{
    private function requiredCertificate(Employee $employee): DigitalCertificate
    {
        $certificate = DigitalCertificate::activeForDni($employee->dni);
        if (!$certificate) {
            throw ValidationException::withMessages(['certificate' => 'Primero debe registrar su certificado RENIEC (.pfx) en su cuenta.']);
        }

        return $certificate;
    }

    private function certificatePublicData(?DigitalCertificate $certificate): ?array
    {
        if (!$certificate) {
            return null;
        }

        return [
            'id' => $certificate->id,
            'subject' => $certificate->certificate_subject,
            'thumbprint' => $certificate->certificate_thumbprint,
            'valid_to' => $certificate->valid_to?->toIso8601String(),
        ];
    }
}
