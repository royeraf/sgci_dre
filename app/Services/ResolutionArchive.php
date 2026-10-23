<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolutionArchive
{
    public function repository(string $code, string $name, string $root): object
    {
        $existing = DB::table('resolution_repositories')->where('code', $code)->first();
        if ($existing) {
            if (rtrim(str_replace('\\', '/', $existing->root_path), '/') !== rtrim(str_replace('\\', '/', $root), '/')) {
                throw new \RuntimeException('La raíz del repositorio cambió. Revise su ubicación antes de indexar.');
            }
            return $existing;
        }
        DB::table('resolution_repositories')->insertOrIgnore(['code' => $code, 'name' => $name, 'root_path' => $root]);
        return DB::table('resolution_repositories')->where('code', $code)->first();
    }

    public function folder(int $repositoryId, string $relative): int
    {
        $relative = $this->validateRelativePath($relative);
        $query = DB::table('resolution_folders')->where('repository_id', $repositoryId)->where('relative_path', $relative);
        $id = $query->value('id');
        if ($id) return (int) $id;
        DB::table('resolution_folders')->insertOrIgnore(['repository_id' => $repositoryId, 'relative_path' => $relative]);
        return (int) $query->value('id');
    }

    public function validateRelativePath(string $relative): string
    {
        $relative = str_replace('\\', '/', $relative);
        if (mb_strlen($relative) > 700 || preg_match('~[\x00-\x1F:]|^/|(^|/)\.{1,2}(/|$)~', $relative)) {
            throw ValidationException::withMessages(['folder' => 'La carpeta contiene una ruta no permitida.']);
        }
        return rtrim($relative, '/');
    }

    public function resolveFile(object $record): string
    {
        $root = realpath($record->root_path);
        $relative = $this->validateRelativePath($record->relative_path);
        abort_unless($root && basename(str_replace('\\', '/', $record->filename)) === $record->filename, 404);
        $path = realpath($root.'/'.$relative.'/'.$record->filename);
        $prefix = rtrim(str_replace('\\', '/', $root), '/').'/';
        $normalized = $path ? str_replace('\\', '/', $path) : '';
        if (PHP_OS_FAMILY === 'Windows') {
            $prefix = mb_strtolower($prefix);
            $normalized = mb_strtolower($normalized);
        }
        abort_unless($path && str_starts_with($normalized, $prefix) && is_file($path), 404, 'El archivo no está disponible. Verifique la unidad del archivo.');
        return $path;
    }
}
