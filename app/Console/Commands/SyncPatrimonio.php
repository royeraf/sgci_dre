<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AssetResponsible;
use App\Models\AssetState;
use App\Models\HrOffice;
use App\Models\PatrimonioAsset;
use Illuminate\Console\Command;

class SyncPatrimonio extends Command
{
    protected $signature = 'patrimonio:sync {--lote= : UUID del lote a sincronizar}';
    protected $description = 'Sincroniza patrimonio_assets con assets';

    public function handle()
    {
        $query = PatrimonioAsset::where('sincronizado', false)
            ->whereNotNull('codigo_activo')
            ->where('codigo_activo', '!=', '');

        if ($lote = $this->option('lote')) {
            $query->where('lote_importacion', $lote);
        }

        $records = $query->get();
        $this->info("Registros a procesar: {$records->count()}");

        if ($records->isEmpty()) {
            $this->info('No hay registros pendientes.');
            return 0;
        }

        $statesCache = AssetState::all()->keyBy(fn($s) => mb_strtoupper(trim($s->nombre)));
        $officesCache = HrOffice::activas()->get()->keyBy(fn($o) => mb_strtoupper(trim($o->nombre)));
        $responsablesCache = AssetResponsible::whereNotNull('nombre_original')->get()
            ->keyBy(fn($r) => mb_strtoupper(trim($r->nombre_original)));

        $sincronizados = 0;
        $omitidos = 0;
        $errores = 0;

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $pa) {
            try {
                $asset = Asset::where('codigo_completo', $pa->codigo_activo)->first();
                if (!$asset) { $omitidos++; $bar->advance(); continue; }

                $updateData = array_filter([
                    'denominacion' => $pa->denominacion,
                    'descripcion'  => $pa->descripcion,
                ]);
                if ($updateData) $asset->update($updateData);

                $estado = $statesCache->get(mb_strtoupper(trim($pa->estado_conserv ?? '')));

                $office = null;
                if ($raw = trim($pa->oficina ?? '')) {
                    $office = $officesCache->get(mb_strtoupper($raw))
                        ?? HrOffice::whereRaw('UPPER(nombre) LIKE ?', ['%'.mb_strtoupper($raw).'%'])->first();
                }

                $responsible = null;
                if ($raw = trim($pa->responsable_final ?? '')) {
                    $key = mb_strtoupper($raw);
                    $responsible = $responsablesCache->get($key);
                    if (!$responsible) {
                        $responsible = AssetResponsible::create([
                            'nombre_original' => strtoupper($raw),
                            'oficina_id'      => $office?->id,
                            'activo'          => true,
                        ]);
                        $responsablesCache->put($key, $responsible);
                    }
                }

                if ($estado || $office || $responsible) {
                    AssetMovement::create([
                        'asset_id'        => $asset->id,
                        'tipo'            => 'ASIGNACION',
                        'fecha'           => $pa->fecha_alta?->toDateString() ?? now()->toDateString(),
                        'estado_id'       => $estado?->id,
                        'oficina_id'      => $office?->id,
                        'responsable_id'  => $responsible?->id,
                        'observaciones'   => 'Sincronizado desde SIGA' . ($pa->archivo_origen ? ' - '.$pa->archivo_origen : ''),
                    ]);
                }

                $pa->update([
                    'asset_id'             => $asset->id,
                    'sincronizado'         => true,
                    'fecha_sincronizacion' => now(),
                ]);

                $sincronizados++;
            } catch (\Exception $e) {
                $errores++;
                $this->newLine();
                $this->error("ERROR [{$pa->codigo_activo}]: " . $e->getMessage());
                $this->line('  ' . $e->getFile() . ':' . $e->getLine());
                if ($errores >= 3) {
                    $this->warn('Deteniendo después de 3 errores para diagnóstico.');
                    break;
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(['Métrica', 'Cantidad'], [
            ['Sincronizados', $sincronizados],
            ['Omitidos', $omitidos],
            ['Errores', $errores],
        ]);

        return 0;
    }
}
