<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SbnCatalogItem;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCatalogoSbnXlsx extends Command
{
    protected $signature = 'import:catalogo-sbn-xlsx {file? : Ruta al archivo XLSX del catálogo SBN}';
    protected $description = 'Importa el Catálogo Nacional de Bienes Muebles del Estado (SBN) desde XLSX';

    private $importedCount = 0;
    private $omittedCount = 0;
    private $errorsCount = 0;

    public function handle()
    {
        $filePath = $this->argument('file') ?? base_path('catalogo_sbn.xlsx');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return 1;
        }

        $this->info("Cargando archivo: {$filePath}");

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            $this->error('No se pudo leer el archivo XLSX: ' . $e->getMessage());
            return 1;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $this->info("Hoja: {$sheet->getTitle()} | Filas: {$highestRow} | Columnas: {$highestCol}");
        $bar = $this->output->createProgressBar($highestRow);
        $bar->start();

        $batch = [];

        // Contexto de bloque: el XLSX agrupa las denominaciones por
        // "GRUPO GENÉRICO" y "CLASE" en filas de cabecera que se repiten.
        $grupoGenerico = null;
        $clase = null;

        for ($row = 1; $row <= $highestRow; $row++) {
            try {
                $colA = trim((string) $sheet->getCell("A{$row}")->getValue());
                $colB = $sheet->getCell("B{$row}")->getValue();

                // Detectar cabecera de bloque: GRUPO GENÉRICO
                if (mb_strtoupper($colA) === 'GRUPO GENÉRICO') {
                    $colC = trim((string) $sheet->getCell("C{$row}")->getValue());
                    $grupoGenerico = $this->stripPrefix($colC);
                    $bar->advance();
                    continue;
                }

                // Detectar cabecera de bloque: CLASE
                if (mb_strtoupper($colA) === 'CLASE') {
                    $colC = trim((string) $sheet->getCell("C{$row}")->getValue());
                    $clase = $this->stripPrefix($colC);
                    $bar->advance();
                    continue;
                }

                // Solo interesan filas con código de 8 dígitos en la columna B
                $codigo = trim((string) $colB);
                if (!preg_match('/^\d{8}$/', $codigo)) {
                    $bar->advance();
                    continue;
                }

                $denominacion = trim((string) $sheet->getCell("C{$row}")->getValue());
                if ($denominacion === '') {
                    $this->omittedCount++;
                    $bar->advance();
                    continue;
                }

                $batch[] = [
                    'codigo' => $codigo,
                    'denominacion' => $denominacion,
                    'grupo_generico' => $grupoGenerico,
                    'clase' => $clase,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $this->importedCount++;

                if (count($batch) >= 200) {
                    SbnCatalogItem::upsert(
                        $batch,
                        ['codigo'],
                        ['denominacion', 'grupo_generico', 'clase', 'updated_at']
                    );
                    $batch = [];
                }
            } catch (\Exception $e) {
                $this->errorsCount++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Fila {$row}: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        // Insertar restantes
        if (count($batch) > 0) {
            SbnCatalogItem::upsert(
                $batch,
                ['codigo'],
                ['denominacion', 'grupo_generico', 'clase', 'updated_at']
            );
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Importación completada:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Importados', $this->importedCount],
                ['Omitidos (sin denominación)', $this->omittedCount],
                ['Errores', $this->errorsCount],
                ['Total en tabla', SbnCatalogItem::count()],
            ]
        );

        return 0;
    }

    /**
     * Extrae el texto después del prefijo "NN: " (ej. "04: AGRÍCOLA Y PESQUERO").
     */
    private function stripPrefix(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}\s*:\s*(.*)$/', $value, $m)) {
            $clean = trim($m[1]);
            return $clean !== '' ? $clean : null;
        }

        return $value;
    }
}