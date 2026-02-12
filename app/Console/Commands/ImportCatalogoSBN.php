<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SbnCatalogItem;

class ImportCatalogoSBN extends Command
{
    protected $signature = 'import:catalogo-sbn {file? : Ruta al archivo CSV del catálogo SBN}';
    protected $description = 'Importa el Catálogo Nacional de Bienes Muebles del Estado (SBN) desde CSV';

    private $importedCount = 0;
    private $updatedCount = 0;
    private $errorsCount = 0;

    public function handle()
    {
        $filePath = $this->argument('file') ?? base_path('catalogo_sbn.csv');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return 1;
        }

        $this->info("Cargando archivo: {$filePath}");

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("No se pudo abrir el archivo.");
            return 1;
        }

        // Leer cabecera
        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("El archivo está vacío.");
            return 1;
        }

        // Contar líneas para progress bar
        $totalLines = 0;
        while (fgetcsv($handle) !== false) {
            $totalLines++;
        }
        rewind($handle);
        fgetcsv($handle); // Saltar cabecera

        $this->info("Registros encontrados: {$totalLines}");
        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            try {
                if (count($row) < 2 || empty($row[0])) {
                    $bar->advance();
                    continue;
                }

                $batch[] = [
                    'codigo' => trim($row[0]),
                    'denominacion' => trim($row[1]),
                    'grupo_generico' => isset($row[2]) ? trim($row[2]) : null,
                    'clase' => isset($row[3]) ? trim($row[3]) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $this->importedCount++;

                if (count($batch) >= 200) {
                    SbnCatalogItem::upsert($batch, ['codigo'], ['denominacion', 'grupo_generico', 'clase', 'updated_at']);
                    $batch = [];
                }
            } catch (\Exception $e) {
                $this->errorsCount++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Error: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        // Insertar restantes
        if (count($batch) > 0) {
            SbnCatalogItem::upsert($batch, ['codigo'], ['denominacion', 'grupo_generico', 'clase', 'updated_at']);
        }

        fclose($handle);

        $bar->finish();
        $this->newLine(2);

        $this->info("Importación completada:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Importados', $this->importedCount],
                ['Errores', $this->errorsCount],
                ['Total en tabla', SbnCatalogItem::count()],
            ]
        );

        return 0;
    }
}
