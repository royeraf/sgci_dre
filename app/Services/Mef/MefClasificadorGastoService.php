<?php

namespace App\Services\Mef;

use App\Models\MefClasificadorGasto;
use App\Models\MefClasificadorGastoHistorial;
use App\Models\MefReglaClasificacionGasto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class MefClasificadorGastoService
{
    public const ESTADO_VALIDADO = 'VALIDADO';

    public const ESTADO_PENDIENTE = 'PENDIENTE';

    public const ESTADO_OBSERVADO = 'OBSERVADO';

    /**
     * Cache de reglas por año dentro de la petición (el servicio se inyecta
     * una sola vez por generación de planilla).
     *
     * @var array<int, BaseCollection<int, MefReglaClasificacionGasto>>
     */
    private array $cacheReglas = [];

    /**
     * Cache de clasificaciones por contexto (año + régimen + modalidad +
     * concepto) dentro de la petición.
     *
     * @var array<string, array{estado: string, clasificador_id: ?string, codigo: ?string, anio_fiscal: int, motivo: string}>
     */
    private array $cacheClasificaciones = [];

    public function buscarCodigo(int $anio, string $codigo): ?MefClasificadorGasto
    {
        $codigo = trim($codigo);

        $directo = MefClasificadorGasto::where('anio', $anio)
            ->where('codigo', $codigo)
            ->first();

        if ($directo) {
            return $directo;
        }

        return MefClasificadorGasto::where('anio', $anio)
            ->where('codigo_alias', $codigo)
            ->first();
    }

    public function buscarDescripcion(int $anio, string $texto): Collection
    {
        return MefClasificadorGasto::where('anio', $anio)
            ->where('descripcion', 'like', '%'.$texto.'%')
            ->orderBy('codigo')
            ->get();
    }

    /**
     * Valida un código contra el catálogo del año fiscal: existencia
     * (directa o por alias), vigencia y estado.
     *
     * @return array<string, mixed>
     */
    public function validarCodigo(int $anio, string $codigo, ?Carbon $fecha = null): array
    {
        $fecha = $fecha ?? now()->startOfDay();
        $clasificador = $this->buscarCodigo($anio, $codigo);

        if (!$clasificador) {
            return [
                'valido' => false,
                'estado' => 'INEXISTENTE',
                'mensaje' => "El código no existe en el catálogo del ejercicio {$anio}.",
                'codigo' => $codigo,
                'clasificador' => null,
            ];
        }

        $esAlias = $clasificador->codigo !== trim($codigo);

        if (!$clasificador->activo) {
            return [
                'valido' => false,
                'estado' => 'INACTIVO',
                'mensaje' => 'El código existe pero está inactivo en el catálogo.',
                'codigo' => $clasificador->codigo,
                'clasificador' => $clasificador,
            ];
        }

        if (!$this->vigenteEn($clasificador, $fecha)) {
            return [
                'valido' => false,
                'estado' => 'NO_VIGENTE',
                'mensaje' => 'El código no está vigente en la fecha consultada.',
                'codigo' => $clasificador->codigo,
                'clasificador' => $clasificador,
            ];
        }

        return [
            'valido' => true,
            'estado' => 'VALIDO',
            'mensaje' => 'Código válido.',
            'codigo' => $clasificador->codigo,
            'es_alias' => $esAlias,
            'clasificador' => $clasificador,
        ];
    }

    public function obtenerPadre(int $anio, string $codigo): ?MefClasificadorGasto
    {
        $clasificador = $this->buscarCodigo($anio, $codigo);

        if (!$clasificador || !$clasificador->codigo_padre) {
            return null;
        }

        return MefClasificadorGasto::where('anio', $anio)
            ->where('codigo', $clasificador->codigo_padre)
            ->first();
    }

    public function obtenerHijos(int $anio, string $codigo): Collection
    {
        $clasificador = $this->buscarCodigo($anio, $codigo);

        if (!$clasificador) {
            return new Collection();
        }

        return MefClasificadorGasto::where('anio', $anio)
            ->where('codigo_padre', $clasificador->codigo)
            ->orderBy('codigo')
            ->get();
    }

    public function obtenerClasificadoresTerminales(int $anio): Collection
    {
        return MefClasificadorGasto::where('anio', $anio)
            ->where('es_terminal', true)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();
    }

    public function buscarRegla(int $anio, ?string $regimen, ?string $modalidad, string $concepto): ?MefReglaClasificacionGasto
    {
        $reglas = $this->reglasDelAnio($anio);

        $candidatas = $reglas->filter(function (MefReglaClasificacionGasto $regla) use ($regimen, $modalidad, $concepto) {
            if (!$regla->activo) {
                return false;
            }

            if ($regla->concepto_codigo !== $concepto) {
                return false;
            }

            if ($regla->regimen !== null && strcasecmp($regla->regimen, (string) $regimen) !== 0) {
                return false;
            }

            if ($regla->modalidad !== null && strcasecmp($regla->modalidad, (string) $modalidad) !== 0) {
                return false;
            }

            return true;
        });

        return $candidatas
            ->sortBy([
                // Más específica primero: régimen + modalidad explícitos ganan.
                fn ($a, $b) => ($b->regimen !== null) <=> ($a->regimen !== null),
                fn ($a, $b) => ($b->modalidad !== null) <=> ($a->modalidad !== null),
                fn ($a, $b) => $a->prioridad <=> $b->prioridad,
            ])
            ->first();
    }

    /**
     * Clasifica un concepto de planilla contra el catálogo y las reglas.
     *
     * @return array{estado: string, clasificador_id: ?string, codigo: ?string, anio_fiscal: int, motivo: string}
     */
    public function clasificar(
        int $anio,
        ?string $regimen,
        ?string $modalidad,
        string $conceptoCodigo,
        ?Carbon $fecha = null
    ): array {
        $regla = $this->buscarRegla($anio, $regimen, $modalidad, $conceptoCodigo);

        if (!$regla) {
            return [
                'estado' => self::ESTADO_PENDIENTE,
                'clasificador_id' => null,
                'codigo' => null,
                'anio_fiscal' => $anio,
                'motivo' => 'Concepto sin regla de clasificación validada para el ejercicio '.$anio.'.',
            ];
        }

        $codigoClasificador = $regla->clasificador?->codigo
            ?? ($regla->clasificador_id
                ? MefClasificadorGasto::where('id', $regla->clasificador_id)->value('codigo')
                : null);

        if (!$codigoClasificador) {
            return [
                'estado' => self::ESTADO_OBSERVADO,
                'clasificador_id' => $regla->clasificador_id,
                'codigo' => null,
                'anio_fiscal' => $anio,
                'motivo' => 'La regla apunta a un clasificador inexistente en el catálogo.',
            ];
        }

        $validacion = $this->validarCodigo($anio, $codigoClasificador, $fecha);

        if (!$validacion['valido']) {
            return [
                'estado' => self::ESTADO_OBSERVADO,
                'clasificador_id' => $regla->clasificador_id,
                'codigo' => $validacion['codigo'] ?: null,
                'anio_fiscal' => $anio,
                'motivo' => $validacion['mensaje'],
            ];
        }

        return [
            'estado' => self::ESTADO_VALIDADO,
            'clasificador_id' => $validacion['clasificador']->id,
            'codigo' => $validacion['clasificador']->codigo,
            'anio_fiscal' => $anio,
            'motivo' => null,
        ];
    }

    /**
     * Clasifica todos los registros (conceptos) de un empleado.
     *
     * @param array<int, array<string, mixed>> $registros
     * @param array<string, string> $codigoPorConceptoId concepto_id => codigo
     * @return array<string, array{estado: string, clasificador_id: ?string, codigo: ?string, anio_fiscal: int, motivo: string}> por concepto_id
     */
    public function clasificarRegistros(
        int $anio,
        string $regimen,
        ?string $modalidad,
        array $registros,
        array $codigoPorConceptoId,
        ?Carbon $fecha = null
    ): array {
        $resultado = [];

        foreach ($registros as $registro) {
            $conceptoId = $registro['concepto_id'] ?? null;
            $codigo = $codigoPorConceptoId[$conceptoId] ?? null;

            if (!$conceptoId || !$codigo) {
                $resultado[$conceptoId ?? ''] = [
                    'estado' => self::ESTADO_PENDIENTE,
                    'clasificador_id' => null,
                    'codigo' => null,
                    'anio_fiscal' => $anio,
                    'motivo' => 'El concepto no tiene código en el catálogo de planilla.',
                ];
                continue;
            }

            $clave = $anio.'|'.$regimen.'|'.($modalidad ?? '').'|'.$codigo;

            if (!isset($this->cacheClasificaciones[$clave])) {
                $this->cacheClasificaciones[$clave] = $this->clasificar($anio, $regimen, $modalidad, $codigo, $fecha);
            }

            $resultado[$conceptoId] = $this->cacheClasificaciones[$clave];
        }

        return $resultado;
    }

    public function registrarCambio(
        MefClasificadorGasto $clasificador,
        string $campo,
        ?string $valorAnterior,
        ?string $valorNuevo,
        ?string $fuente = null,
        ?int $usuarioId = null
    ): MefClasificadorGastoHistorial {
        return MefClasificadorGastoHistorial::create([
            'clasificador_id' => $clasificador->id,
            'campo' => $campo,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevo,
            'fecha_cambio' => now(),
            'fuente' => $fuente,
            'usuario_id' => $usuarioId,
        ]);
    }

    public function historial(MefClasificadorGasto $clasificador): Collection
    {
        return MefClasificadorGastoHistorial::where('clasificador_id', $clasificador->id)
            ->orderByDesc('fecha_cambio')
            ->get();
    }

    public function vigenteEn(MefClasificadorGasto $clasificador, Carbon $fecha): bool
    {
        if ($clasificador->fecha_inicio && $fecha->lt($clasificador->fecha_inicio)) {
            return false;
        }

        if ($clasificador->fecha_fin && $fecha->gt($clasificador->fecha_fin)) {
            return false;
        }

        return true;
    }

    private function reglasDelAnio(int $anio): BaseCollection
    {
        if (!isset($this->cacheReglas[$anio])) {
            $this->cacheReglas[$anio] = MefReglaClasificacionGasto::with('clasificador')
                ->where('anio', $anio)
                ->orderBy('prioridad')
                ->get();
        }

        return $this->cacheReglas[$anio];
    }
}
