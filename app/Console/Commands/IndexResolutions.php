<?php

namespace App\Console\Commands;

use App\Services\ResolutionArchive;
use App\Services\ResolutionFilenameParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IndexResolutions extends Command
{
    protected $signature = 'resoluciones:indexar {--limit=0 : Limitar archivos para una comprobación}';
    protected $description = 'Indexa nombres y tamaños del archivo configurado; no lee PDF, no usa OCR, no mueve archivos.';

    public function handle(ResolutionArchive $archive, ResolutionFilenameParser $parser): int
    {
        $root = config('resolutions.archive_root');
        if (!$root || !is_dir($root)) {
            $this->error('Configure RESOLUTIONS_ARCHIVE_ROOT con una carpeta disponible.');
            return self::FAILURE;
        }
        $lock = Cache::lock('resolutions-index', 7200);
        if (!$lock->get()) { $this->error('Ya hay una indexación en curso.'); return self::FAILURE; }
        try {
            $root = realpath($root);
            $repository = $archive->repository('archivo', 'Archivo institucional', $root);
            $series = DB::table('resolution_series')->pluck('id', 'code')->all();
            $folders = [];
            $preferredByDirectory = [];
            $revisions = app(\App\Services\ResolutionRevisionPolicy::class);
            $batch = [];
            $count = $bytes = $review = $skipped = 0;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isLink() || !$file->isFile() || !preg_match('/\.\s*pdf$/i', $file->getFilename())) { $skipped++; continue; }
                if ($parser->isAuxiliary($file->getFilename())) { $skipped++; continue; }
                $directory = $file->getPath();
                if (!isset($preferredByDirectory[$directory])) {
                    $preferredByDirectory[$directory] = [];
                    foreach (scandir($directory) as $name) {
                        $original = $revisions->originalName($name);
                        if ($original !== null && is_file($directory.DIRECTORY_SEPARATOR.$name) && !is_link($directory.DIRECTORY_SEPARATOR.$name)) $preferredByDirectory[$directory][mb_strtolower($original)] = true;
                    }
                }
                if ($revisions->originalName($file->getFilename()) === null && isset($preferredByDirectory[$directory][mb_strtolower($file->getFilename())])) { $skipped++; continue; }
                $folder = str_replace('\\', '/', substr($file->getPath(), strlen($root)));
                $folder = trim($folder, '/');
                $folderId = $folders[$folder] ??= $archive->folder($repository->id, $folder);
                $meta = $parser->parse($file->getFilename(), $folder);
                $size = $file->getSize();
                $batch[] = ['folder_id' => $folderId, 'filename' => $file->getFilename(), 'series_id' => $series[$meta['series']] ?? null,
                    'year' => $meta['year'], 'number_start' => $meta['number_start'], 'number_end' => $meta['number_end'], 'size_bytes' => $size];
                $count++; $bytes += $size;
                if (!$meta['year'] || !$meta['number_start'] || !$meta['series']) $review++;
                if (count($batch) >= 500) {
                    $this->flush($batch);
                    $this->line(number_format($count).' PDF registrados / actualizados.');
                }
                if ((int) $this->option('limit') > 0 && $count >= (int) $this->option('limit')) break;
            }
            $this->flush($batch);
            Cache::forget('resolutions-summary');
            Cache::forget('resolutions-coverage');
            Cache::forget('resolutions-year-counts-v2');
            $this->info(json_encode(['pdf' => $count, 'bytes' => $bytes, 'revisar' => $review, 'otros_omitidos' => $skipped], JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        } finally { $lock->release(); }
    }

    private function flush(array &$batch): void
    {
        if (!$batch) return;
        // Existing identifiers (including later manual corrections) are preserved.
        DB::table('resolutions')->upsert($batch, ['folder_id', 'filename'], ['size_bytes']);
        $batch = [];
    }
}
