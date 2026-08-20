<?php

namespace App\Services;

use App\Models\PapeletaRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Builds the operational QR revision of a certified one-page papeleta.
 *
 * The source is always the last PDF revision, never a Blade/Dompdf redraw.
 * This preserves the original signatures and appends only values to fields
 * reserved before the first signature was made.
 */
class PapeletaExecutionPdfService
{
    public function refresh(PapeletaRequest $papeleta): void
    {
        if (!$papeleta->qr_form_enabled) {
            return;
        }

        $papeleta->loadMissing('signatures');
        $source = $this->sourceDocument($papeleta);
        if (!$source) {
            return;
        }

        $temporaryDirectory = Storage::disk('local')->path('signature-temp');
        if (!is_dir($temporaryDirectory) && !mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
            throw new \RuntimeException('No se pudo preparar el documento QR final.');
        }

        $temporaryOutput = $temporaryDirectory.'/qr-'.Str::random(32).'.pdf';
        $destinationSignature = $papeleta->destino_firma_path
            ? Storage::disk('local')->path($papeleta->destino_firma_path)
            : '';
        $destinationName = $papeleta->destino_firmado_at
            ? 'NOMBRES Y APELLIDOS: '.$papeleta->destino_firmante_nombre
            : '';
        $destinationDni = $papeleta->destino_firmado_at
            ? 'DNI: '.($papeleta->destino_firmante_dni ?: '-')
            : '';
        $destinationCoordinates = $papeleta->destino_latitude !== null && $papeleta->destino_longitude !== null
            ? sprintf('COORDENADAS: X: %.6f, Y: %.6f', (float) $papeleta->destino_longitude, (float) $papeleta->destino_latitude)
            : '';

        try {
            $process = new Process([
                (string) config('services.server_signing.python'),
                base_path('tools/update_papeleta_qr_fields.py'),
                '--input', Storage::disk('local')->path($source),
                '--output', $temporaryOutput,
                '--salida', $papeleta->salida_real_at?->format('H:i') ?? '',
                '--retorno', $papeleta->retorno_real_at?->format('H:i') ?? '',
                '--destino-firma', $destinationSignature,
                '--destino-nombre', $destinationName,
                '--destino-dni', $destinationDni,
                '--destino-coordenadas', $destinationCoordinates,
                '--latitude', (string) ($papeleta->destino_latitude ?? ''),
                '--longitude', (string) ($papeleta->destino_longitude ?? ''),
            ], base_path());
            $process->setTimeout(45)->run();

            if (!$process->isSuccessful() || !is_file($temporaryOutput)) {
                report(new \RuntimeException('Error actualizando campos QR: '.$process->getErrorOutput()));
                throw new \RuntimeException('No se pudo actualizar la papeleta final con la información QR.');
            }

            $binary = file_get_contents($temporaryOutput);
            if ($binary === false || !str_starts_with($binary, '%PDF-') || !str_contains($binary, '/ByteRange')) {
                throw new \RuntimeException('La actualización QR no produjo un PDF firmado válido.');
            }

            $path = 'papeletas/executed/papeleta-'.$papeleta->numero_papeleta.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.pdf';
            Storage::disk('local')->put($path, $binary);
            $papeleta->update(['executed_document_path' => $path]);
        } finally {
            @unlink($temporaryOutput);
        }
    }

    private function sourceDocument(PapeletaRequest $papeleta): ?string
    {
        if ($papeleta->executed_document_path && Storage::disk('local')->exists($papeleta->executed_document_path)) {
            return $papeleta->executed_document_path;
        }

        return $papeleta->signatures
            ->filter(fn ($signature) => $signature->signed_document_path !== 'pending')
            ->sortByDesc('signed_at')
            ->map(fn ($signature) => $signature->signed_document_path)
            ->first(fn ($path) => Storage::disk('local')->exists($path));
    }
}
