<?php

namespace App\Services;

use App\Models\VehicleCommission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Fills the departure/return data (real hours, km, fuel, P/N) into a
 * vehicle commission's already-signed PDF, as incremental updates over
 * fields reserved before the first signature. The source is always the
 * last PDF revision, never a Blade/Dompdf redraw — this preserves the
 * three signatures and only appends values to the reserved fields.
 */
class VehicleCommissionExecutionPdfService
{
    public function refresh(VehicleCommission $commission): void
    {
        // Whichever revision is genuinely the newest — a signature applied
        // after this fill was last run, or a previous fill — is the correct
        // base. See VehicleCommission::latestDocumentPath().
        $source = $commission->latestDocumentPath();
        if (!$source) {
            return;
        }

        $temporaryDirectory = Storage::disk('local')->path('signature-temp');
        if (!is_dir($temporaryDirectory) && !mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
            throw new \RuntimeException('No se pudo preparar el documento final de la comisión.');
        }

        $temporaryOutput = $temporaryDirectory.'/veh-'.Str::random(32).'.pdf';

        try {
            $process = new Process([
                (string) config('services.server_signing.python'),
                base_path('tools/update_vehicle_commission_fields.py'),
                '--input', Storage::disk('local')->path($source),
                '--output', $temporaryOutput,
                '--hora-salida', $commission->hora_salida ? substr($commission->hora_salida, 0, 5) : '',
                '--hora-retorno', $commission->hora_regreso ? substr($commission->hora_regreso, 0, 5) : '',
                '--km-salida', (string) ($commission->km_salida ?? ''),
                '--km-retorno', (string) ($commission->km_retorno ?? ''),
                '--combustible', (string) ($commission->combustible ?? ''),
                '--total-km', (string) ($commission->total_km_recorrido ?? ''),
                '--autorizador-nombre', (string) ($commission->autorizado_por_nombre ?? ''),
                '--autorizador-dni', $commission->autorizadorEmployee?->dni ? 'DNI: '.$commission->autorizadorEmployee->dni : '',
            ], base_path());
            $process->setTimeout(45)->run();

            if (!$process->isSuccessful() || !is_file($temporaryOutput)) {
                report(new \RuntimeException('Error actualizando datos de la comisión: '.$process->getErrorOutput()));
                throw new \RuntimeException('No se pudo actualizar la autorización con los datos de ejecución.');
            }

            $binary = file_get_contents($temporaryOutput);
            if ($binary === false || !str_starts_with($binary, '%PDF-') || !str_contains($binary, '/ByteRange')) {
                throw new \RuntimeException('La actualización no produjo un PDF firmado válido.');
            }

            $path = 'vehicle-commissions/executed/comision-'.$commission->anio.'-'.$commission->numero.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.pdf';
            Storage::disk('local')->put($path, $binary);
            $commission->update([
                'executed_document_path' => $path,
                'executed_document_revision' => $commission->nextDocumentRevision(),
            ]);
        } finally {
            @unlink($temporaryOutput);
        }
    }
}
