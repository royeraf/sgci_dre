<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AssetOrigin;
use App\Models\AssetResponsible;
use App\Models\AssetState;
use App\Models\HrOffice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportInventario extends Command
{
    protected $signature = 'import:inventario
                            {file : Ruta al archivo CSV generado por export_to_laravel.py}
                            {--fresh : Elimina assets y movimientos existentes antes de importar}';

    protected $description = 'Importa el inventario completo (assets + oficinas + responsables + movimientos) desde el CSV exportado del sistema de barras';

    private array $originsCache    = [];
    private array $statesCache     = [];
    private array $officesCache    = [];
    private array $responsiblesCache = [];

    private int $imported = 0;
    private int $skipped  = 0;
    private int $errors   = 0;

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $filePath = base_path($filePath);
        }

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return 1;
        }

        if ($this->option('fresh')) {
            $this->warn('Modo FRESH activado: se eliminarán todos los assets y movimientos existentes.');

            if (!$this->confirm('¿Confirmas la eliminación de todos los datos de patrimonio?')) {
                $this->info('Operación cancelada.');
                return 0;
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            AssetMovement::truncate();
            Asset::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->info('Datos eliminados.');
            $this->newLine();
        }

        $this->loadCaches();

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);

        if (!$headers) {
            $this->error('El archivo CSV está vacío o no tiene cabecera.');
            return 1;
        }

        // Limpiar BOM UTF-8 del primer header si existe
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $row);
        }
        fclose($handle);

        $total = count($rows);
        $this->info("Registros encontrados: {$total}");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($rows as $row) {
            try {
                $this->processRow($row);
            } catch (\Exception $e) {
                $this->errors++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Error [{$row['codigo_completo']}]: " . $e->getMessage());
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Importados',             $this->imported],
                ['Omitidos (duplicados)',  $this->skipped],
                ['Errores',                $this->errors],
            ]
        );

        return 0;
    }

    private function loadCaches(): void
    {
        AssetOrigin::all()->each(function ($o) {
            $this->originsCache[mb_strtoupper(trim($o->nombre))] = $o->id;
        });

        AssetState::all()->each(function ($s) {
            $this->statesCache[mb_strtoupper(trim($s->nombre))] = $s->id;
        });

        HrOffice::all()->each(function ($o) {
            $this->officesCache[mb_strtoupper(trim($o->nombre))] = $o->id;
        });

        AssetResponsible::whereNotNull('nombre_original')->get()->each(function ($r) {
            $this->responsiblesCache[mb_strtoupper(trim($r->nombre_original))] = $r->id;
        });
    }

    private function processRow(array $row): void
    {
        $codigoPatrimonial = trim($row['codigo_patrimonial'] ?? '');
        $codigoInterno     = strtoupper(trim($row['codigo_interno'] ?? ''));
        $detalleBien       = trim($row['detalle_bien'] ?? '');
        $descripcion       = trim($row['descripcion'] ?? '') ?: null;
        $oficinaNombre     = trim($row['oficina'] ?? '');
        $tipoRegistro      = strtoupper(trim($row['tipo_registro'] ?? ''));
        $codigoCompleto    = trim($row['codigo_completo'] ?? '');
        $estadoNombre      = strtoupper(trim($row['estado'] ?? ''));
        $responsableNombre = trim($row['responsable'] ?? '');

        if (empty($codigoPatrimonial) || empty($detalleBien)) {
            $this->skipped++;
            return;
        }

        if (Asset::where('codigo_completo', $codigoCompleto)->exists()) {
            $this->skipped++;
            return;
        }

        $origenId    = $this->resolveOrCreate($this->originsCache, $tipoRegistro, fn($nombre) =>
            AssetOrigin::firstOrCreate(['nombre' => $nombre], ['descripcion' => 'Importado desde inventario barras'])->id
        );

        $estadoId    = $this->resolveOrCreate($this->statesCache, $estadoNombre, fn($nombre) =>
            AssetState::firstOrCreate(['nombre' => $nombre], ['descripcion' => 'Importado desde inventario barras', 'orden' => 99])->id
        );

        $oficinaId   = $this->resolveOffice($oficinaNombre);

        $responsableId = $this->resolveResponsible($responsableNombre, $oficinaId);

        $asset = Asset::create([
            'codigo_patrimonio' => $codigoPatrimonial,
            'codigo_interno'    => $codigoInterno,
            'codigo_completo'   => $codigoCompleto,
            'denominacion'      => $detalleBien,
            'descripcion'       => $descripcion,
            'origen_id'         => $origenId,
            'codigo_barras'     => $codigoCompleto,
        ]);

        if ($estadoId || $oficinaId || $responsableId) {
            AssetMovement::create([
                'asset_id'       => $asset->id,
                'tipo'           => 'ASIGNACION',
                'fecha'          => now()->toDateString(),
                'estado_id'      => $estadoId,
                'oficina_id'     => $oficinaId,
                'responsable_id' => $responsableId,
                'observaciones'  => 'Importado desde inventario de barras DRE',
            ]);
        }

        $this->imported++;
    }

    private function resolveOrCreate(array &$cache, string $key, callable $creator): ?int
    {
        if (empty($key)) {
            return null;
        }

        if (!isset($cache[$key])) {
            $cache[$key] = $creator($key);
        }

        return $cache[$key];
    }

    private function resolveOffice(string $nombre): ?string
    {
        if (empty($nombre)) {
            return null;
        }

        $key = mb_strtoupper($nombre);

        if (!isset($this->officesCache[$key])) {
            $office = HrOffice::whereRaw('UPPER(nombre) = ?', [$key])->first()
                ?? HrOffice::create([
                    'nombre' => ucwords(strtolower($nombre)),
                    'activo' => true,
                ]);

            $this->officesCache[$key] = $office->id;
        }

        return $this->officesCache[$key];
    }

    private function resolveResponsible(string $nombre, ?string $oficinaId): ?int
    {
        if (empty($nombre)) {
            return null;
        }

        $key = mb_strtoupper($nombre);

        if (!isset($this->responsiblesCache[$key])) {
            $responsible = AssetResponsible::whereRaw('UPPER(nombre_original) = ?', [$key])->first()
                ?? AssetResponsible::create([
                    'nombre_original' => $nombre,
                    'oficina_id'      => $oficinaId,
                    'activo'          => true,
                ]);

            $this->responsiblesCache[$key] = $responsible->id;
        }

        return $this->responsiblesCache[$key];
    }
}
