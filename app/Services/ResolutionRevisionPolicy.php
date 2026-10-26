<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ResolutionRevisionPolicy
{
    public function originalName(string $name): ?string
    {
        return preg_match('/\[R\]\.pdf$/iu', $name) ? preg_replace('/(?:\s*\[R\])+(?=\.pdf$)/iu', '', $name) : null;
    }

    public function preferredNames(string $name): array
    {
        if ($this->originalName($name) !== null) return [];
        $stem = preg_replace('/\.pdf$/iu', '', $name);
        return [$stem.'[R].pdf', $stem.' [R].pdf'];
    }

    public function preferredExists(int $folderId, string $name): bool
    {
        return DB::table('resolutions')->where('folder_id', $folderId)->whereIn('filename', $this->preferredNames($name))->exists();
    }

    /** Called in the upload transaction, only after the preferred PDF is saved. */
    public function retireOriginal(int $folderId, string $name, string $target): void
    {
        $original = $this->originalName($name);
        if ($original === null) return;
        $row = DB::table('resolutions')->where('folder_id', $folderId)->where('filename', $original)->lockForUpdate()->first();
        if (!$row) return;
        $source = $target.DIRECTORY_SEPARATOR.$row->filename;
        $backup = storage_path('app/backups/resolution-revisions/'.date('Ymd').'/'.$row->id.'-'.bin2hex(random_bytes(6)));
        if (!is_dir($backup) && !mkdir($backup, 0700, true)) throw new \RuntimeException('No se pudo respaldar la versión anterior.');
        if (!is_file($source) || is_link($source) || !copy($source, $backup.'/original.pdf') || hash_file('sha256', $source) !== hash_file('sha256', $backup.'/original.pdf')) throw new \RuntimeException('No se pudo verificar el respaldo de la versión anterior.');
        if (file_put_contents($backup.'/metadata.json', json_encode($row, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)) === false) throw new \RuntimeException('No se pudo respaldar la identificación anterior.');
        DB::table('resolutions')->where('id', $row->id)->delete();
        DB::afterCommit(static fn() => @unlink($source));
    }
}
