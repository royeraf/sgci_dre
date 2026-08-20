<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DigitalCertificate;
use App\Services\DigitalCertificateVault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigitalCertificateController extends Controller
{
    public function __construct(private readonly DigitalCertificateVault $vault)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signer_dni' => 'required|digits:8',
            'pfx_file' => 'required|file|max:10240',
            'pfx_password' => 'required|string|max:191',
            'signing_pin' => ['required', 'string', 'min:6', 'max:20', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ], [
            'signing_pin.regex' => 'La nueva clave debe contener letras y números.',
            'signing_pin.confirmed' => 'La confirmación de la nueva clave no coincide.',
        ]);

        if (!in_array(strtolower($request->file('pfx_file')->getClientOriginalExtension()), ['pfx', 'p12'], true)) {
            return response()->json(['message' => 'Seleccione un archivo con extensión .pfx o .p12.'], 422);
        }

        $certificate = $this->vault->register(
            $request->file('pfx_file'),
            $validated['pfx_password'],
            $validated['signing_pin'],
            $validated['signer_dni'],
            auth()->id()
        );

        AuditLog::log(auth()->id(), 'Registrar Certificado RENIEC', 'Se registró o renovó el certificado del DNI '.$validated['signer_dni'], DigitalCertificate::class, null);

        return response()->json([
            'message' => 'Certificado RENIEC guardado en la bóveda privada.',
            'certificate' => $this->publicData($certificate),
        ], 201);
    }

    public function storeMine(Request $request): JsonResponse
    {
        $dni = (string) auth()->user()?->dni;
        if (!preg_match('/^\d{8}$/', $dni)) {
            return response()->json(['message' => 'Su cuenta no tiene un DNI válido asociado.'], 422);
        }

        $validated = $request->validate([
            'pfx_file' => 'required|file|max:10240',
            'pfx_password' => 'required|string|max:191',
            'signing_pin' => ['required', 'string', 'min:6', 'max:20', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'consent' => 'required|accepted',
        ], [
            'signing_pin.regex' => 'La nueva clave debe contener letras y números.',
            'signing_pin.confirmed' => 'La confirmación de la nueva clave no coincide.',
        ]);

        if (!in_array(strtolower($request->file('pfx_file')->getClientOriginalExtension()), ['pfx', 'p12'], true)) {
            return response()->json(['message' => 'Seleccione un archivo con extensión .pfx o .p12.'], 422);
        }

        $certificate = $this->vault->register(
            $request->file('pfx_file'),
            $validated['pfx_password'],
            $validated['signing_pin'],
            $dni,
            auth()->id()
        );

        AuditLog::log(auth()->id(), 'Registrar mi certificado RENIEC', 'El titular otorgó consentimiento expreso y registró o renovó voluntariamente su certificado.', DigitalCertificate::class, $certificate->id);

        return response()->json([
            'message' => 'Su certificado quedó cifrado y vinculado a su cuenta.',
            'certificate' => $this->publicData($certificate),
        ], 201);
    }

    private function publicData(DigitalCertificate $certificate): array
    {
        return [
            'id' => $certificate->id,
            'signer_dni' => $certificate->signer_dni,
            'certificate_subject' => $certificate->certificate_subject,
            'certificate_thumbprint' => $certificate->certificate_thumbprint,
            'valid_to' => $certificate->valid_to?->toIso8601String(),
        ];
    }
}
