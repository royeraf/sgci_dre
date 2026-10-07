<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Genera los subconjuntos woff2 de la tipografía que DomPDF incrusta en las
 * boletas, para que la vista previa en pantalla use exactamente el mismo
 * diseño que el PDF.
 *
 * DomPDF solo trae DejaVu Sans como familia sans y no admite woff2, así que
 * hay que reducir el TTF (757 KB) a los glifos que usa la boleta (~8 KB) con
 * fonttools. Requiere `pyftsubset` en el PATH; si no está, avisa y termina
 * con error para que nadie dé por hecho que el preview ya coincide.
 */
class GenerarFuenteBoleta extends Command
{
    protected $signature = 'planillas:fuente-boleta
                            {--force : Regenerar aunque los archivos ya existan}';

    protected $description = 'Genera los subconjuntos woff2 de DejaVu Sans para la vista previa de boletas';

    /**
     * Glifos que puede imprimir una boleta: ASCII, latín-1 (acentos y ñ),
     * rayas y comillas tipográficas, viñeta y grado.
     */
    private const UNICODES = 'U+0020-007E,U+00A0-00FF,U+2013-2014,U+2018-2019,U+201C-201D,U+2022,U+00B0,U+0020';

    /**
     * family de DomPDF => peso CSS. El nombre del archivo de salida conserva
     * el de la familia para que la vista previa referencie la misma fuente.
     */
    private const PESOS = [
        'DejaVuSans' => 'normal',
        'DejaVuSans-Bold' => 'bold',
    ];

    public function handle()
    {
        $fontDir = $this->directorioFuentesDomPdf();

        if (!$fontDir) {
            $this->error('No se encontró el directorio de fuentes de DomPDF.');
            return 1;
        }

        if (!$this->tienePyftsubset()) {
            $this->error('`pyftsubset` no está disponible (fonttools).');
            $this->line('  Instálalo con: pip install fonttools brotli');
            $this->warn('  Mientras tanto la vista previa usará la sans del navegador y no coincidirá con el PDF.');
            return 1;
        }

        $destino = public_path('fonts');

        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        foreach (self::PESOS as $familia => $peso) {
            $origen = $fontDir . '/' . $familia . '.ttf';
            $salida = $destino . '/' . $familia . '.woff2';

            if (!is_file($origen)) {
                $this->error("No se encontró la fuente {$familia}.ttf en {$fontDir}");
                return 1;
            }

            if (is_file($salida) && !$this->option('force')) {
                $this->line("  = {$familia}.woff2 ya existe (usa --force para regenerar)");
                continue;
            }

            $comando = sprintf(
                'pyftsubset %s --output-file=%s --flavor=woff2 --unicodes=%s --layout-features= --no-hinting --desubroutinize',
                escapeshellarg($origen),
                escapeshellarg($salida),
                escapeshellarg(self::UNICODES)
            );

            exec($comando . ' 2>/dev/null', $salidaTexto, $codigo);

            if ($codigo !== 0 || !is_file($salida)) {
                $this->error("  falló el subconjunto de {$familia}");
                return 1;
            }

            $this->info(sprintf(
                '  ✓ %s (peso %s): %s → %s',
                basename($salida),
                $peso,
                $this->legible(filesize($origen)),
                $this->legible(filesize($salida))
            ));
        }

        return 0;
    }

    /**
     * DomPDF resuelve las fuentes core en su fontDir y, si no las encuentra,
     * en las que trae empaquetadas en lib/fonts. El binding `dompdf.options`
     * del paquete es el array de configuración, no el objeto Options, así que
     * el directorio se lee de la configuración.
     */
    private function directorioFuentesDomPdf(): ?string
    {
        $directorios = array_filter([
            config('dompdf.options.font_dir'),
            base_path('vendor/dompdf/dompdf/lib/fonts'),
        ]);

        foreach ($directorios as $dir) {
            if (is_file($dir . '/DejaVuSans.ttf')) {
                return $dir;
            }
        }

        return null;
    }

    private function tienePyftsubset(): bool
    {
        exec('command -v pyftsubset 2>/dev/null', $ruta, $codigo);

        return $codigo === 0 && $ruta !== [];
    }

    private function legible(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024, 1) . ' KB';
    }
}
