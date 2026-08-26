<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use App\Models\Employee;
use App\Models\VehicleCommission;
use App\Models\VehicleCommissionSignature;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class VehicleCommissionSigningService
{
    // A5 landscape points. These boxes sit in the empty gap between the
    // "Huánuco, <fecha>" line and each signature line of
    // pdf.vehicle_exit_authorization (three equal columns). Verified by
    // rendering a real commission PDF with a test PFX and inspecting the
    // page at 150dpi — see plan verification notes.
    private const BOXES = [
        'SOLICITANTE' => '32,110,193,168',
        'AUTORIZADOR' => '215,110,376,168',
        'CONDUCTOR' => '398,110,559,168',
    ];

    public function __construct(private readonly DigitalCertificateVault $vault)
    {
    }

    public function sign(VehicleCommission $commission, Employee $employee, string $role, DigitalCertificate $certificate, string $pin): VehicleCommissionSignature
    {
        if ($commission->signatures()->where('signer_role', $role)->exists()) {
            throw ValidationException::withMessages(['signature' => 'Esta etapa ya fue firmada.']);
        }

        $unlocked = $this->vault->unlock($certificate, $pin);
        $tempDirectory = Storage::disk('local')->path('signature-temp');
        if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0700, true) && !is_dir($tempDirectory)) {
            throw new \RuntimeException('No se pudo preparar el directorio temporal de firma.');
        }

        $signature = new VehicleCommissionSignature([
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
        $commission->signatures()->save($signature);

        $nonce = Str::random(32);
        $pfxPath = $tempDirectory.'/'.$nonce.'.pfx';
        $outputPath = $tempDirectory.'/'.$nonce.'.pdf';
        file_put_contents($pfxPath, $unlocked['pfx']);
        chmod($pfxPath, 0600);

        try {
            $sourcePath = Storage::disk('local')->path($this->documentToSign($commission));
            $fieldName = 'Comision_'.$role.'_'.$signature->id;
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
                '--reason', 'Autorización de Salida de Vehículos N° '.$commission->numero.'-'.$commission->anio,
                '--position', $employee->cargo ?? '-',
                '--signed-at', $signature->signed_at->format('d/m/Y H:i:s'),
                '--logo', public_path('images/logo.png'),
                '--box', self::BOXES[$role] ?? self::BOXES['SOLICITANTE'],
            ];

            $process = new Process($command, base_path(), [
                'DREH_PFX_PASSWORD' => $unlocked['password'],
                'DREH_SIGN_REASON' => 'Autorización Salida de Vehículos '.$commission->numero.'-'.$commission->anio.' - '.$role,
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

            $path = 'vehicle-commissions/signed/'.$commission->anio.'-'.$commission->numero.'-'.$role.'-'.$signature->id.'.pdf';
            Storage::disk('local')->put($path, $binary);
            $signature->update([
                'signed_document_path' => $path,
                'document_sha256' => hash('sha256', $binary),
                'signed_at' => now(),
                'document_revision' => $commission->nextDocumentRevision(),
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

    private function documentToSign(VehicleCommission $commission): string
    {
        // Departure/return data can be registered between two signature
        // steps (e.g. "Gestionar" is open to editing at any state). The next
        // signature must build on top of whichever revision is actually
        // newest — never unconditionally the raw last-signature file — or
        // an execution update applied earlier silently disappears once this
        // signature is applied over the older bytes.
        if ($previous = $commission->latestDocumentPath()) {
            return $previous;
        }

        $commission->loadMissing(['vehicle', 'solicitanteEmployee.person', 'conductorEmployee.person', 'conductorEmployee.driverLicense', 'autorizadorEmployee.person']);
        $renderedPath = 'vehicle-commissions/unsigned/comision-'.$commission->anio.'-'.$commission->numero.'-base.pdf';
        Storage::disk('local')->put($renderedPath, Pdf::loadView('pdf.vehicle_exit_authorization', [
            'commission' => $commission,
            // hora_salida (falls back to the scheduled hour) and combustible
            // (settable at creation) would otherwise bake a value into this
            // page before the reserved field for it even exists. Real
            // execution data always arrives later, through that field.
            'reservingFields' => true,
        ])->output());

        // The departure/return data is only known after the driver confirms
        // (all 3 signatures done). These fields must be reserved before the
        // first signature so they can be filled later as an incremental
        // update, without altering already-signed bytes.
        $preparedPath = 'vehicle-commissions/unsigned/comision-'.$commission->anio.'-'.$commission->numero.'.pdf';
        $preparedAbsolutePath = Storage::disk('local')->path($preparedPath);
        $process = new Process([
            (string) config('services.server_signing.python'),
            base_path('tools/prepare_vehicle_commission_fields.py'),
            '--input', Storage::disk('local')->path($renderedPath),
            '--output', $preparedAbsolutePath,
        ], base_path());
        $process->setTimeout(30)->run();

        if (!$process->isSuccessful() || !is_file($preparedAbsolutePath)) {
            report(new \RuntimeException('Error preparando campos de la comisión: '.$process->getErrorOutput()));
            throw new \RuntimeException('No se pudo preparar la autorización para registrar los datos de ejecución.');
        }

        return $preparedPath;
    }
}
