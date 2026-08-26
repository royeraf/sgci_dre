<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use App\Models\Employee;
use App\Models\PapeletaRequest;
use App\Models\PapeletaRequestSignature;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class PapeletaRequestSigningService
{
    public function __construct(private readonly DigitalCertificateVault $vault)
    {
    }

    public function sign(PapeletaRequest $papeleta, Employee $employee, string $role, DigitalCertificate $certificate, string $pin): PapeletaRequestSignature
    {
        if ($papeleta->signatures()->where('signer_role', $role)->exists()) {
            throw ValidationException::withMessages(['signature' => 'Esta etapa ya fue firmada.']);
        }

        $unlocked = $this->vault->unlock($certificate, $pin);
        $tempDirectory = Storage::disk('local')->path('signature-temp');
        if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0700, true) && !is_dir($tempDirectory)) {
            throw new \RuntimeException('No se pudo preparar el directorio temporal de firma.');
        }

        $signature = new PapeletaRequestSignature([
            'signer_employee_id' => $employee->id,
            'digital_certificate_id' => $certificate->id,
            'signer_role' => $role,
            'signer_dni' => $employee->dni,
            'signer_name' => $employee->full_name,
            'certificate_thumbprint' => $certificate->certificate_thumbprint,
            'signed_document_path' => 'pending',
            'document_sha256' => str_repeat('0', 64),
            'signed_at' => now(),
        ]);
        $papeleta->signatures()->save($signature);

        $nonce = Str::random(32);
        $pfxPath = $tempDirectory.'/'.$nonce.'.pfx';
        $outputPath = $tempDirectory.'/'.$nonce.'.pdf';
        file_put_contents($pfxPath, $unlocked['pfx']);
        chmod($pfxPath, 0600);

        try {
            $papeleta->loadMissing('reason');
            $sourcePath = Storage::disk('local')->path($this->documentToSign($papeleta));
            $usesReservedFields = $papeleta->qr_form_enabled;
            $fieldName = $usesReservedFields
                ? 'Papeleta_'.$role
                : 'Papeleta_'.$role.'_'.$signature->id;
            $command = [
                (string) config('services.server_signing.python'),
                (string) config('services.server_signing.script'),
                '--input', $sourcePath,
                '--output', $outputPath,
                '--pfx', $pfxPath,
                '--field', $fieldName,
                '--role', $role,
                '--signer', $employee->full_name,
                '--dni', $employee->dni,
                '--reason', $papeleta->reason?->nombre ?? 'Papeleta de salida',
                '--position', $employee->cargo ?? '-',
                '--signed-at', $signature->signed_at->format('d/m/Y H:i:s'),
                '--logo', public_path('images/logo.png'),
            ];

            // The first signature certifies new QR-enabled documents with a
            // strict FormFill policy. Existing papeletas keep their original
            // signing behaviour and are never converted after the fact.
            if ($role === 'SERVIDOR' && $papeleta->qr_form_enabled) {
                $command[] = '--certify';
            }
            if ($usesReservedFields) {
                $command[] = '--existing-field';
            }

            $process = new Process($command, base_path(), [
                'DREH_PFX_PASSWORD' => $unlocked['password'],
                'DREH_SIGN_REASON' => 'Papeleta '.$papeleta->numero_papeleta.' - '.$role,
                'DREH_SIGN_LOCATION' => 'DRE Huánuco',
            ]);
            $process->setTimeout(90)->run();
            if (!$process->isSuccessful() || !is_file($outputPath)) {
                report(new \RuntimeException('Error de pyHanko: '.$process->getErrorOutput()));
                throw ValidationException::withMessages(['signing_pin' => 'No se pudo firmar. Verifique su clave de firma.']);
            }

            $binary = file_get_contents($outputPath);
            if ($binary === false || !str_starts_with($binary, '%PDF-') || !str_contains($binary, '/ByteRange')) {
                throw new \RuntimeException('El resultado no contiene una firma PDF válida.');
            }

            $path = 'papeletas/signed/'.$papeleta->numero_papeleta.'-'.$role.'-'.$signature->id.'.pdf';
            Storage::disk('local')->put($path, $binary);
            $signature->update([
                'signed_document_path' => $path,
                'document_sha256' => hash('sha256', $binary),
                'signed_at' => now(),
            ]);

            return $signature;
        } catch (\Throwable $exception) {
            $signature->delete();
            throw $exception;
        } finally {
            @unlink($pfxPath);
            @unlink($outputPath);
            if (is_string($unlocked['password'])) sodium_memzero($unlocked['password']);
            if (is_string($unlocked['pfx'])) sodium_memzero($unlocked['pfx']);
        }
    }

    private function documentToSign(PapeletaRequest $papeleta): string
    {
        $previous = $papeleta->signatures()->where('signed_document_path', '!=', 'pending')->latest('signed_at')->first();
        if ($previous && Storage::disk('local')->exists($previous->signed_document_path)) {
            return $previous->signed_document_path;
        }

        $papeleta->load(['employee.person', 'employee.direction', 'employee.office', 'employee.position', 'employee.contractType', 'reason']);
        $path = 'papeletas/unsigned/papeleta-'.$papeleta->numero_papeleta.'.pdf';
        Storage::disk('local')->put($path, Pdf::loadView('pdf.papeleta_request', [
            'papeleta' => $papeleta,
            // The PAdES engine writes the visual signature on this source PDF.
            'showSignatureCards' => false,
        ])->setPaper('a5', 'portrait')->output());
        if (!$papeleta->qr_form_enabled) {
            return $path;
        }

        // The form fields must exist before the certification signature.  The
        // separate preparer only creates empty widgets in reserved cells; it
        // never sees a certificate nor a signing PIN.
        $preparedPath = 'papeletas/unsigned/papeleta-'.$papeleta->numero_papeleta.'-qr-v1.pdf';
        $preparedAbsolutePath = Storage::disk('local')->path($preparedPath);
        $process = new Process([
            (string) config('services.server_signing.python'),
            base_path('tools/prepare_papeleta_qr_fields.py'),
            '--input', Storage::disk('local')->path($path),
            '--output', $preparedAbsolutePath,
        ], base_path());
        $process->setTimeout(30)->run();

        if (!$process->isSuccessful() || !is_file($preparedAbsolutePath)) {
            report(new \RuntimeException('Error preparando campos QR: '.$process->getErrorOutput()));
            throw new \RuntimeException('No se pudo preparar la papeleta para el control QR.');
        }

        return $preparedPath;
    }
}
