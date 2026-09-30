<?php

namespace App\Console\Commands;

use App\Models\MefClasificadorGasto;
use App\Services\Mef\MefClasificadorGastoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MefSyncGastoCommand extends Command
{
    protected $signature = 'mef:sync-gasto
        {anio : Año fiscal del catálogo}
        {--file= : Ruta del archivo (csv, json o xlsx). Por defecto busca en storage/app/mef/}
        {--dry-run : Muestra los cambios sin escribirlos}';

    protected $description = 'Importa/actualiza el catálogo Clasificador Económico de Gastos (MEF) desde un archivo CSV, JSON o XLSX';

    private const COLUMNAS = [
        'codigo', 'codigo_alias', 'codigo_padre', 'nivel', 'descripcion',
        'es_terminal', 'activo', 'fecha_inicio', 'fecha_fin', 'fuente', 'version_catalogo',
    ];

    public function handle(MefClasificadorGastoService $mef): int
    {
        $anio = (int) $this->argument('anio');

        if ($anio < 2000 || $anio > 2100) {
            $this->error('El año debe estar entre 2000 y 2100.');

            return self::FAILURE;
        }

        $ruta = $this->resolverRuta($anio);

        if (!$ruta) {
            $this->error("No se encontró el archivo. Colóquelo en storage/app/mef/catalogo-{$anio}.csv|.json|.xlsx o use --file.");

            return self::FAILURE;
        }

        try {
            $filas = $this->leerArchivo($ruta);
        } catch (Throwable $e) {
            $this->error('No se pudo leer el archivo: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($filas)) {
            $this->error('El archivo no contiene filas de datos.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $creados = 0;
        $actualizados = 0;
        $sinCambio = 0;
        $descartados = 0;
        $errores = [];

        DB::transaction(function () use ($mef, $filas, $anio, $ruta, $dryRun, &$creados, &$actualizados, &$sinCambio, &$descartados, &$errores) {
            $vistos = [];

            foreach ($filas as $indice => $fila) {
                $numero = $indice + 2;
                $datos = $this->normalizar($fila);

                if (empty($datos['codigo'])) {
                    $descartados++;
                    $errores[] = "Fila {$numero}: el código es obligatorio.";
                    continue;
                }

                if (!preg_match('/^\d+(\.\d+)*$/', $datos['codigo'])) {
                    $descartados++;
                    $errores[] = "Fila {$numero}: código «{$datos['codigo']}» inválido (formato 2.1.1.13.1.1).";
                    continue;
                }

                $existente = MefClasificadorGasto::where('anio', $anio)
                    ->where('codigo', $datos['codigo'])
                    ->first();

                if (!$existente && empty($datos['descripcion'])) {
                    $descartados++;
                    $errores[] = "Fila {$numero}: la descripción es obligatoria para códigos nuevos.";
                    continue;
                }

                $nuevo = [
                    'anio' => $anio,
                    'codigo' => $datos['codigo'],
                    'codigo_alias' => $existente?->codigo_alias,
                    'codigo_padre' => $existente?->codigo_padre,
                    'nivel' => $existente?->nivel,
                    'descripcion' => $existente?->descripcion,
                    'es_terminal' => $existente?->es_terminal ?? false,
                    'activo' => $existente?->activo ?? true,
                    'fecha_inicio' => $existente?->fecha_inicio,
                    'fecha_fin' => $existente?->fecha_fin,
                    'fuente' => $existente?->fuente,
                    'version_catalogo' => $existente?->version_catalogo,
                ];

                foreach ($datos as $columna => $valor) {
                    if ($valor !== null || in_array($columna, ['codigo_alias', 'codigo_padre', 'fecha_inicio', 'fecha_fin', 'version_catalogo'], true)) {
                        $nuevo[$columna] = $valor;
                    }
                }

                $nuevo['fuente'] = $nuevo['fuente'] ?? 'Importado desde '.$ruta;

                $vistos[] = $datos['codigo'];

                if (!$existente) {
                    if (!$dryRun) {
                        $existente = MefClasificadorGasto::create($nuevo);
                        $mef->registrarCambio($existente, 'registro', null, $this->resumen($nuevo), $nuevo['fuente'], null);
                    }
                    $creados++;
                    continue;
                }

                $cambios = [];

                foreach (self::COLUMNAS as $columna) {
                    if ($columna === 'anio' || $columna === 'codigo') {
                        continue;
                    }

                    if ($this->texto($existente->{$columna}) !== $this->texto($nuevo[$columna])) {
                        $cambios[$columna] = [$this->texto($existente->{$columna}), $this->texto($nuevo[$columna])];
                    }
                }

                if (empty($cambios)) {
                    $sinCambio++;
                    continue;
                }

                $actualizados++;

                if (!$dryRun) {
                    $existente->update($nuevo);

                    foreach ($cambios as $columna => [$anterior, $nuevoValor]) {
                        $mef->registrarCambio($existente, $columna, $anterior, $nuevoValor, $nuevo['fuente'], null);
                    }
                }
            }

            return $vistos;
        });

        $this->table(
            ['Operación', 'Cantidad'],
            [
                ['Creados', $creados],
                ['Actualizados', $actualizados],
                ['Sin cambios', $sinCambio],
                ['Descartados', $descartados],
                ['Filas leídas', count($filas)],
            ]
        );

        if (!empty($errores)) {
            $this->newLine();
            $this->warn('Filas descartadas:');

            foreach ($errores as $error) {
                $this->warn('  - '.$error);
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('Modo dry-run: no se escribió ningún cambio.');
        } else {
            $this->newLine();
            $this->info("Catálogo del ejercicio {$anio} sincronizado ({$this->contar($anio)} registros vigentes).");
        }

        return $creados + $actualizados + $sinCambio > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function contar(int $anio): int
    {
        return MefClasificadorGasto::where('anio', $anio)->count();
    }

    private function resolverRuta(int $anio): ?string
    {
        $file = $this->option('file');

        if ($file) {
            return is_file($file) ? $file : null;
        }

        foreach (['csv', 'xlsx', 'json'] as $extension) {
            $ruta = storage_path("app/mef/catalogo-{$anio}.{$extension}");

            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private function leerArchivo(string $ruta): array
    {
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => $this->leerCsv($ruta),
            'json' => $this->leerJson($ruta),
            'xlsx' => $this->leerXlsx($ruta),
            default => throw new \RuntimeException("Formato .{$extension} no soportado (use csv, json o xlsx)."),
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function leerCsv(string $ruta): array
    {
        $filas = [];
        $contenido = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $encabezado = null;

        foreach ($contenido as $linea) {
            $columnas = str_getcsv($linea);

            if ($encabezado === null) {
                $encabezado = $columnas;
                continue;
            }

            $filas[] = array_combine($encabezado, $columnas);
        }

        return $filas;
    }

    /** @return array<int, array<string, mixed>> */
    private function leerJson(string $ruta): array
    {
        $datos = json_decode(file_get_contents($ruta), true);

        if (!is_array($datos)) {
            throw new \RuntimeException('El JSON debe ser un arreglo de objetos.');
        }

        return array_values($datos);
    }

    /** @return array<int, array<string, mixed>> */
    private function leerXlsx(string $ruta): array
    {
        $hoja = \PhpOffice\PhpSpreadsheet\IOFactory::load($ruta)->getActiveSheet();
        $filas = $hoja->toArray();
        $encabezado = array_shift($filas);

        return array_values(array_map(
            fn ($fila) => array_combine($encabezado, $fila),
            array_filter($filas, fn ($fila) => array_filter($fila, fn ($v) => $v !== null && $v !== '') !== [])
        ));
    }

    /** @return array<string, mixed> */
    private function normalizar(array $fila): array
    {
        $salida = [];

        foreach ($fila as $clave => $valor) {
            $claveNormalizado = $this->clave((string) $clave);

            if (!in_array($claveNormalizado, self::COLUMNAS, true)) {
                continue;
            }

            $salida[$claveNormalizado] = $valor;
        }

        if (array_key_exists('nivel', $salida)) {
            $salida['nivel'] = ($salida['nivel'] === null || $salida['nivel'] === '') ? null : (int) $salida['nivel'];
        }

        foreach (['es_terminal', 'activo'] as $booleano) {
            if (array_key_exists($booleano, $salida)) {
                $salida[$booleano] = $this->booleano($salida[$booleano], $booleano === 'activo');
            }
        }

        foreach (['codigo', 'codigo_alias', 'codigo_padre', 'descripcion', 'fuente', 'version_catalogo'] as $texto) {
            if (array_key_exists($texto, $salida)) {
                $valor = $salida[$texto];
                $salida[$texto] = ($valor === null || trim((string) $valor) === '') ? null : trim((string) $valor);
            }
        }

        foreach (['fecha_inicio', 'fecha_fin'] as $fecha) {
            if (!array_key_exists($fecha, $salida)) {
                continue;
            }

            $valor = $salida[$fecha];

            if ($valor instanceof \PhpOffice\PhpSpreadsheet\Shared\Date\DateTime) {
                $valor = $valor->format('Y-m-d');
            } elseif (is_numeric($valor)) {
                $valor = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor)->format('Y-m-d');
            }

            $salida[$fecha] = $valor ? (string) $valor : null;
        }

        return $salida;
    }

    private function clave(string $clave): string
    {
        $clave = mb_strtolower(trim($clave));
        $clave = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $clave) ?? $clave;

        return str_replace([' ', '-', '.'], '_', $clave);
    }

    private function booleano($valor, bool $defecto): bool
    {
        if ($valor === null || $valor === '') {
            return $defecto;
        }

        if (is_bool($valor)) {
            return $valor;
        }

        $texto = mb_strtolower(trim((string) $valor));

        return in_array($texto, ['1', 'true', 'si', 's', 'x', 'activo'], true);
    }

    private function texto($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if ($valor instanceof \Carbon\CarbonInterface) {
            return $valor->format('Y-m-d');
        }

        return (string) $valor;
    }

    private function resumen(array $datos): string
    {
        return $datos['codigo'].' — '.$datos['descripcion'];
    }
}
