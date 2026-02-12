<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AssetOrigin;
use App\Models\AssetState;

class ImportBienesCSV extends Command
{
    protected $signature = 'import:bienes {file? : Ruta al archivo CSV} {--fresh : Eliminar bienes existentes antes de importar}';
    protected $description = 'Importa bienes desde archivo CSV a la tabla assets (solo datos básicos, sin responsables ni oficinas)';

    private $originsCache = [];
    private $statesCache = [];
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errorsCount = 0;

    public function handle()
    {
        if ($this->option('fresh')) {
            $this->warn("Modo FRESH activado: eliminando bienes existentes...");

            if (!$this->confirm('¿Estás seguro de eliminar todos los bienes y movimientos?')) {
                $this->info('Operación cancelada.');
                return 0;
            }

            \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            AssetMovement::truncate();
            Asset::truncate();
            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info("Datos eliminados correctamente.");
            $this->newLine();
        }

        $filePath = $this->argument('file') ?? base_path('bienes_202602121009.csv');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return 1;
        }

        $this->info("Cargando archivo: {$filePath}");

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (\Exception $e) {
            $this->error("Error al cargar el archivo: " . $e->getMessage());
            return 1;
        }

        // Pre-cargar caches
        $this->loadCaches();

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $totalRows = $highestRow - 1;

        $this->info("Registros encontrados: {$totalRows}");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalRows);
        $bar->start();

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $this->processRow($sheet, $row);
            } catch (\Exception $e) {
                $this->errorsCount++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Error en fila {$row}: " . $e->getMessage());
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Importación completada:");
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Importados', $this->importedCount],
                ['Omitidos (duplicados)', $this->skippedCount],
                ['Errores', $this->errorsCount],
            ]
        );

        return 0;
    }

    private function loadCaches(): void
    {
        // Cache de orígenes
        AssetOrigin::all()->each(function ($origin) {
            $this->originsCache[mb_strtoupper(trim($origin->nombre), 'UTF-8')] = $origin->id;
        });

        // Cache de estados
        AssetState::all()->each(function ($state) {
            $this->statesCache[mb_strtoupper(trim($state->nombre), 'UTF-8')] = $state->id;
        });
    }

    private function processRow($sheet, int $row): void
    {
        $codigoPatrimonial = trim($sheet->getCell("B{$row}")->getValue() ?? '');
        $codigoInterno = trim($sheet->getCell("C{$row}")->getValue() ?? '');
        $detalleBien = trim($sheet->getCell("D{$row}")->getValue() ?? '');
        $descripcion = trim($sheet->getCell("E{$row}")->getValue() ?? '');
        $tipoRegistro = trim($sheet->getCell("H{$row}")->getValue() ?? '');
        $codigoCompleto = trim($sheet->getCell("I{$row}")->getValue() ?? '');
        $estado = trim($sheet->getCell("J{$row}")->getValue() ?? '');

        // Validar campos mínimos
        if (empty($codigoPatrimonial) || empty($detalleBien)) {
            $this->skippedCount++;
            return;
        }

        // Verificar duplicados
        if (Asset::where('codigo_completo', $codigoCompleto)->exists()) {
            $this->skippedCount++;
            return;
        }

        // Buscar origen
        $origenKey = mb_strtoupper(trim($tipoRegistro), 'UTF-8');
        $origenId = $this->originsCache[$origenKey] ?? null;

        if (!$origenId && !empty($tipoRegistro)) {
            $origin = AssetOrigin::firstOrCreate(
                ['nombre' => $origenKey],
                ['descripcion' => 'Creado durante importación CSV']
            );
            $origenId = $origin->id;
            $this->originsCache[$origenKey] = $origenId;
        }

        // Buscar estado
        $estadoKey = mb_strtoupper(trim($estado), 'UTF-8');
        $estadoId = $this->statesCache[$estadoKey] ?? null;

        if (!$estadoId && !empty($estado)) {
            $state = AssetState::firstOrCreate(
                ['nombre' => $estadoKey],
                ['descripcion' => 'Creado durante importación CSV', 'orden' => 99]
            );
            $estadoId = $state->id;
            $this->statesCache[$estadoKey] = $estadoId;
        }

        // Crear asset
        $asset = Asset::create([
            'codigo_patrimonio' => $codigoPatrimonial,
            'codigo_interno' => strtoupper($codigoInterno),
            'codigo_completo' => $codigoCompleto,
            'denominacion' => $detalleBien,
            'descripcion' => $descripcion ?: null,
            'origen_id' => $origenId,
            'codigo_barras' => $codigoCompleto,
        ]);

        // Crear movimiento inicial (solo con estado, sin responsable ni oficina)
        if ($estadoId) {
            AssetMovement::create([
                'asset_id' => $asset->id,
                'tipo' => 'ASIGNACION',
                'fecha' => now()->toDateString(),
                'estado_id' => $estadoId,
                'observaciones' => 'Importado desde CSV de bienes',
            ]);
        }

        $this->importedCount++;
    }
}
