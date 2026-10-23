<?php

namespace App\Http\Controllers;

use App\Services\ResolutionArchive;
use App\Services\ResolutionFilenameParser;
use App\Services\ResolutionSearch;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResolutionController extends Controller
{
    public function index(Request $request, ResolutionSearch $search, ResolutionFilenameParser $parser)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:120', 'year' => 'nullable|integer|between:1900,'.now('America/Lima')->year,
            'number' => 'nullable|integer|min:1|max:999999', 'series' => 'nullable|integer|exists:resolution_series,id',
            'review' => 'nullable|in:1', 'page' => 'nullable|integer|min:1',
            'status' => 'nullable|in:all,grouped,review,untyped',
            'sort' => 'nullable|in:recent,oldest,name',
            'folder' => 'nullable|integer|exists:resolution_folders,id',
        ]);
        $interpreted = $search->interpret($filters['q'] ?? '');
        if ($interpreted['number']) {
            if ($interpreted['year'] && $interpreted['year'] > now('America/Lima')->year) {
                throw \Illuminate\Validation\ValidationException::withMessages(['q' => 'El año no puede ser posterior a '.now('America/Lima')->year.'.']);
            }
            $filters['q'] = '';
            $filters['number'] = $interpreted['number'];
            if ($interpreted['year']) $filters['year'] = $interpreted['year'];
            if ($interpreted['series']) $filters['series'] = DB::table('resolution_series')->where('code', $interpreted['series'])->value('id');
        }
        $filters['status'] = $filters['status'] ?? (!empty($filters['review']) ? 'review' : 'all');
        $query = DB::table('resolutions as r')
            ->join('resolution_folders as f', 'f.id', '=', 'r.folder_id')
            ->join('resolution_repositories as p', 'p.id', '=', 'f.repository_id')
            ->leftJoin('resolution_series as s', 's.id', '=', 'r.series_id');
        if (!empty($filters['year'])) $query->where('r.year', $filters['year']);
        if (!empty($filters['series'])) $query->where('r.series_id', $filters['series']);
        if (!empty($filters['number'])) {
            $query->where('r.number_start', '<=', $filters['number'])
                ->where(fn($q) => $q->where('r.number_end', '>=', $filters['number'])
                    ->orWhere(fn($single) => $single->whereNull('r.number_end')->where('r.number_start', $filters['number'])));
        }
        foreach (array_slice(preg_split('/\s+/u', trim($filters['q'] ?? ''), -1, PREG_SPLIT_NO_EMPTY), 0, 8) as $word) {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word);
            $column = DB::getDriverName() === 'mysql' ? 'r.filename COLLATE utf8mb4_unicode_ci' : 'r.filename';
            $query->whereRaw("$column LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
        }
        if ($filters['status'] === 'review') {
            $query->where(fn($q) => $q->whereNull('r.year')->orWhereNull('r.number_start')->orWhereNull('r.series_id'));
        }
        if ($filters['status'] === 'grouped') $query->whereNotNull('r.number_end');
        if ($filters['status'] === 'untyped') $query->whereNull('r.series_id');
        // Folder counts follow the search, but remain available when opening one folder.
        $folders = (clone $query)->select('f.id', 'f.relative_path as path', 'p.name as repository')
            ->selectRaw('COUNT(*) as total')->groupBy('f.id', 'f.relative_path', 'p.name')
            ->orderBy('f.relative_path')->get();
        if (!empty($filters['folder'])) $query->where('r.folder_id', $filters['folder']);
        $currentFolder = !empty($filters['folder']) ? DB::table('resolution_folders as f')
            ->join('resolution_repositories as p', 'p.id', '=', 'f.repository_id')
            ->where('f.id', $filters['folder'])->select('f.id', 'f.relative_path as path', 'p.name as repository')->first() : null;
        $sort = $filters['sort'] ?? 'recent';
        if ($sort === 'name') $query->orderBy('r.filename');
        else $query->orderByRaw('r.year IS NULL')->orderBy('r.year', $sort === 'oldest' ? 'asc' : 'desc')->orderByRaw('r.number_start IS NULL')->orderBy('r.number_start');
        $rows = $query->select('r.*', 'f.relative_path as folder', 'p.name as repository', 's.code as series')
            ->orderBy('r.id')->paginate(30);
        // Canonical filters keep next/previous pages aligned with an interpreted identifier.
        $rows->appends(array_filter($filters, fn($v, $k) => $k !== 'page' && $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH));
        $rows->getCollection()->transform(function ($row) use ($parser) {
            $row->title = $parser->title($row->filename);
            $row->detected = $parser->parse($row->filename, $row->folder);
            return $row;
        });
        $summary = Cache::remember('resolutions-summary', 60, fn() => [
            'total' => DB::table('resolutions')->count(),
            'bytes' => (int) DB::table('resolutions')->sum('size_bytes'),
            'review' => DB::table('resolutions')->where(fn($q) => $q->whereNull('year')->orWhereNull('number_start')->orWhereNull('series_id'))->count(),
            'folders' => DB::table('resolution_folders')->count(),
            'grouped' => DB::table('resolutions')->whereNotNull('number_end')->count(),
        ]);
        return Inertia::render('Resoluciones/Index', [
            'records' => $rows, 'filters' => $filters, 'summary' => $summary,
            'coverage' => app(\App\Services\ResolutionCoverage::class)->report(),
            'access' => app(\App\Services\ResolutionAccess::class)->forUser($request->user()),
            'folders' => $folders, 'currentFolder' => $currentFolder,
            'years' => DB::table('resolutions')->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year'),
            'yearCounts' => Cache::remember('resolutions-year-counts-v2', 60, fn() => DB::table('resolutions')->whereNotNull('year')->selectRaw('year, COUNT(*) as total, SUM(CASE WHEN series_id IS NULL OR number_start IS NULL THEN 1 ELSE 0 END) as review')->groupBy('year')->orderByDesc('year')->get()),
            'annualBySeries' => DB::table('resolutions as r')->leftJoin('resolution_series as s', 's.id', '=', 'r.series_id')
                ->select('r.year', 's.code as series')->selectRaw('COUNT(*) as total')->groupBy('r.year', 's.code')->orderByDesc('r.year')->get(),
            'seriesOptions' => DB::table('resolution_series')->get(['id', 'code', 'name']),
            'maxUploadMb' => (int) config('resolutions.max_upload_kb') / 1024,
            'currentYear' => now('America/Lima')->year,
        ]);
    }

    public function file(Request $request, int $resolution, ResolutionArchive $archive)
    {
        $record = DB::table('resolutions as r')
            ->join('resolution_folders as f', 'f.id', '=', 'r.folder_id')
            ->join('resolution_repositories as p', 'p.id', '=', 'f.repository_id')
            ->where('r.id', $resolution)->select('r.filename', 'f.relative_path', 'p.root_path')->first();
        abort_unless($record, 404);
        $path = $archive->resolveFile($record);
        $headers = ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        return $request->boolean('download')
            ? response()->download($path, $record->filename, $headers)
            : response()->file($path, $headers);
    }

    public function coverage(Request $request, \App\Services\ResolutionCoverage $coverage)
    {
        $data = $request->validate(['year'=>'nullable|integer|between:1900,'.now('America/Lima')->year]);
        return response()->json($coverage->report(isset($data['year']) ? (int)$data['year'] : null));
    }

    public function uploadFromArchive(Request $request, ResolutionArchive $archive, ResolutionFilenameParser $parser)
    {
        $data = $request->validate([
            'filename' => 'required|string|max:255', 'folder' => 'nullable|string|max:700',
            'year' => 'nullable|integer|between:1900,'.now('America/Lima')->year,
        ]);
        abort_if($parser->isAuxiliary($data['filename']), 422, 'Archivo auxiliar excluido: no se carga como resolución.');
        $root = config('resolutions.archive_root');
        abort_unless($root && is_dir($root), 422, 'El archivo local no está disponible en el servidor. Seleccione el PDF desde una ruta más corta.');
        // Only this configured archive may supply files; never accept an absolute source from the browser.
        try {
            $source = $archive->resolveFile((object) [
                'root_path' => $root, 'relative_path' => $data['folder'] ?? '', 'filename' => $data['filename'],
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            abort(422, 'No se encontró este PDF dentro del archivo local configurado. Seleccione el archivo desde una ruta más corta.');
        }
        // A trusted server file enters the same size/MIME/header/duplicate checks as a browser upload.
        $local = new UploadedFile($source, $data['filename'], 'application/pdf', UPLOAD_ERR_OK, true);
        $localRequest = Request::createFromBase($request->duplicate(files: ['file' => $local]));
        return $this->upload($localRequest, $archive, $parser);
    }

    public function upload(Request $request, ResolutionArchive $archive, ResolutionFilenameParser $parser)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:pdf|max:'.config('resolutions.max_upload_kb'),
            'folder' => 'nullable|string|max:700', 'year' => 'nullable|integer|between:1900,'.now('America/Lima')->year,
        ]);
        $file = $request->file('file');
        // Five signature bytes on a newly uploaded file, never OCR or bulk content extraction.
        abort_unless(file_get_contents($file->getRealPath(), false, null, 0, 5) === '%PDF-', 422, 'El archivo no contiene una cabecera PDF válida.');
        $name = $file->getClientOriginalName();
        abort_if($parser->isAuxiliary($name), 422, 'Archivo auxiliar excluido: no se carga como resolución.');
        abort_if(mb_strlen($name) > 255 || preg_match('~[<>:"/\\\\|?*\x00-\x1F]|[. ]$~u', $name)
            || preg_match('/^(CON|PRN|AUX|NUL|COM\d|LPT\d)(\.|$)/i', $name), 422, 'El nombre del archivo no es válido.');
        abort_unless(preg_match('/\.pdf$/i', $name), 422, 'Seleccione un archivo PDF.');
        $relative = $archive->validateRelativePath($data['folder'] ?? '');
        $root = config('resolutions.uploads_root');
        if (!is_dir($root)) mkdir($root, 0750, true);
        $root = realpath($root);
        $target = $root.($relative !== '' ? '/'.$relative : '');
        // Reject a symlink in any existing segment before creating directories.
        $cursor = $root;
        foreach (array_filter(explode('/', $relative), 'strlen') as $segment) {
            $cursor .= '/'.$segment;
            abort_if(is_link($cursor), 422, 'La carpeta no está permitida.');
        }
        if (!is_dir($target)) mkdir($target, 0750, true);
        $target = realpath($target);
        abort_unless($target && ($target === $root || str_starts_with($target, $root.DIRECTORY_SEPARATOR)), 422);
        $repository = $archive->repository('cargas', 'Archivos cargados', $root);
        $folderId = $archive->folder($repository->id, $relative);
        $meta = $parser->parse($name, $relative);
        if (!empty($data['year'])) $meta['year'] = (int) $data['year'];
        $path = $target.DIRECTORY_SEPARATOR.$name;
        abort_if(DB::table('resolutions')->where('folder_id', $folderId)->where('filename', $name)->exists(), 409, 'Este nombre ya existe en la carpeta. No se sobrescribió.');
        $out = @fopen($path, 'xb');
        abort_unless($out, is_file($path) ? 409 : 500, 'No se pudo guardar el archivo; compruebe duplicados y espacio disponible.');
        $in = fopen($file->getRealPath(), 'rb');
        try {
            $written = stream_copy_to_stream($in, $out);
            if ($written !== $file->getSize()) throw new \RuntimeException('No se pudo completar la escritura del PDF.');
            fclose($out); $out = null;
            $id = DB::table('resolutions')->insertGetId([
                'folder_id' => $folderId, 'filename' => $name,
                'series_id' => DB::table('resolution_series')->where('code', $meta['series'])->value('id'),
                'year' => $meta['year'], 'number_start' => $meta['number_start'], 'number_end' => $meta['number_end'], 'size_bytes' => $written,
            ]);
        } catch (\Throwable $e) {
            if (is_resource($out)) fclose($out);
            @unlink($path);
            throw $e;
        } finally { if (is_resource($in)) fclose($in); }
        Cache::forget('resolutions-summary');
        Cache::forget('resolutions-coverage');
        Cache::forget('resolutions-year-counts-v2');
        return response()->json(['id' => $id, 'message' => 'PDF registrado.'], 201);
    }

    public function update(Request $request, int $resolution)
    {
        $data = $request->validate([
            'series_id' => 'nullable|integer|exists:resolution_series,id', 'year' => 'nullable|integer|between:1900,'.now('America/Lima')->year,
            'number_start' => 'nullable|required_with:number_end|integer|min:1|max:999999',
            'number_end' => 'nullable|integer|gte:number_start|max:999999',
        ]);
        abort_unless(DB::table('resolutions')->where('id', $resolution)->exists(), 404);
        DB::table('resolutions')->where('id', $resolution)->update($data);
        Cache::forget('resolutions-summary');
        Cache::forget('resolutions-coverage');
        Cache::forget('resolutions-year-counts-v2');
        return back()->with('success', 'Identificación actualizada. El PDF y su nombre original se mantienen.');
    }
}
