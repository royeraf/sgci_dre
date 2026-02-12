<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Models\SbnCatalogItem;

class NormalizarDenominaciones extends Command
{
    protected $signature = 'assets:normalizar-denominaciones {--dry-run : Mostrar cambios sin aplicar}';
    protected $description = 'Normaliza las denominaciones de bienes usando el Catálogo SBN';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("Modo DRY-RUN: solo se mostrarán los cambios, no se aplicarán.");
            $this->newLine();
        }

        // Pre-cargar catálogo SBN indexado por código
        $catalog = SbnCatalogItem::pluck('denominacion', 'codigo')->toArray();

        if (empty($catalog)) {
            $this->error("El catálogo SBN está vacío. Ejecuta primero: php artisan import:catalogo-sbn");
            return 1;
        }

        $this->info("Catálogo SBN cargado: " . count($catalog) . " denominaciones");

        $assets = Asset::all();
        $this->info("Bienes a procesar: " . $assets->count());
        $this->newLine();

        $normalizedCount = 0;
        $noChangeCount = 0;
        $notFoundCount = 0;
        $changes = [];

        $bar = $this->output->createProgressBar($assets->count());
        $bar->start();

        foreach ($assets as $asset) {
            $code = $asset->codigo_patrimonio;

            if (!isset($catalog[$code])) {
                $notFoundCount++;
                $bar->advance();
                continue;
            }

            $newDenom = $catalog[$code];
            $oldDenom = $asset->denominacion;

            if ($oldDenom === $newDenom) {
                $noChangeCount++;
                $bar->advance();
                continue;
            }

            $changes[] = [
                $asset->codigo_completo,
                mb_substr($oldDenom, 0, 50),
                mb_substr($newDenom, 0, 50),
            ];

            if (!$isDryRun) {
                $asset->update(['denominacion' => $newDenom]);
            }

            $normalizedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info("Resultado:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Normalizados' . ($isDryRun ? ' (pendientes)' : ''), $normalizedCount],
                ['Sin cambio (ya coinciden)', $noChangeCount],
                ['Sin coincidencia en catálogo', $notFoundCount],
                ['Total procesados', $assets->count()],
            ]
        );

        // Mostrar primeros cambios
        if (count($changes) > 0) {
            $this->newLine();
            $this->info("Cambios" . ($isDryRun ? ' pendientes' : ' aplicados') . " (primeros 20):");
            $this->table(
                ['Código', 'Denominación anterior', 'Denominación SBN'],
                array_slice($changes, 0, 20)
            );
        }

        return 0;
    }
}
