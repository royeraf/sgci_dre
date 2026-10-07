<?php

namespace App\Http\Controllers;

use App\Models\MefClasificadorGasto;
use App\Models\MefReglaClasificacionGasto;
use App\Services\Mef\MefClasificadorGastoService;
use Illuminate\Http\Request;

class MefClasificadorGastoController extends Controller
{
    public function __construct(private readonly MefClasificadorGastoService $mef)
    {
    }

    // ========== CATÁLOGO ==========

    public function index(Request $request)
    {
        $datos = $request->validate([
            'anio' => 'nullable|integer|min:2000|max:2100',
            'codigo' => 'nullable|string|max:30',
            'search' => 'nullable|string|max:200',
            'nivel' => 'nullable|integer|min:1|max:10',
            'estado' => 'nullable|in:activo,inactivo',
            'terminal' => 'nullable|boolean',
        ]);

        $anio = (int) ($datos['anio'] ?? now()->year);

        $query = MefClasificadorGasto::where('anio', $anio)
            ->orderBy('codigo');

        if (!empty($datos['codigo'])) {
            $query->where('codigo', 'like', $datos['codigo'].'%');
        }

        if (!empty($datos['search'])) {
            $texto = trim($datos['search']);
            $query->where(function ($q) use ($texto) {
                $q->where('descripcion', 'like', '%'.$texto.'%')
                    ->orWhere('codigo', 'like', '%'.$texto.'%')
                    ->orWhere('codigo_alias', 'like', '%'.$texto.'%');
            });
        }

        if (isset($datos['nivel'])) {
            $query->where('nivel', (int) $datos['nivel']);
        }

        if (($datos['estado'] ?? null) === 'activo') {
            $query->where('activo', true);
        } elseif (($datos['estado'] ?? null) === 'inactivo') {
            $query->where('activo', false);
        }

        if (array_key_exists('terminal', $datos) && $datos['terminal'] !== null) {
            $query->where('es_terminal', (bool) $datos['terminal']);
        }

        return response()->json($query->get());
    }

    public function show(string $anio, string $codigo)
    {
        $clasificador = $this->mef->buscarCodigo((int) $anio, $codigo);

        if (!$clasificador) {
            return response()->json([
                'message' => "El código {$codigo} no existe en el catálogo del ejercicio {$anio}.",
            ], 404);
        }

        return response()->json($clasificador);
    }

    public function hijos(string $anio, string $codigo)
    {
        return response()->json($this->mef->obtenerHijos((int) $anio, $codigo));
    }

    public function validar(string $anio, string $codigo)
    {
        return response()->json($this->mef->validarCodigo((int) $anio, $codigo));
    }

    public function store(Request $request)
    {
        $datos = $this->validarClasificador($request);

        $existe = MefClasificadorGasto::where('anio', $datos['anio'])
            ->where('codigo', $datos['codigo'])
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => "El código {$datos['codigo']} ya existe en el catálogo del ejercicio {$datos['anio']}.",
            ], 422);
        }

        if (!empty($datos['codigo_padre']) && !MefClasificadorGasto::where('anio', $datos['anio'])
            ->where('codigo', $datos['codigo_padre'])
            ->exists()) {
            return response()->json([
                'message' => "El código padre {$datos['codigo_padre']} no existe en el catálogo del ejercicio {$datos['anio']}.",
            ], 422);
        }

        $clasificador = MefClasificadorGasto::create($datos);

        $this->mef->registrarCambio(
            $clasificador,
            'registro',
            null,
            $this->resumen($clasificador),
            $clasificador->fuente,
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Clasificador registrado correctamente.',
            'clasificador' => $clasificador,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $clasificador = MefClasificadorGasto::find($id);

        if (!$clasificador) {
            return response()->json(['message' => 'Clasificador no encontrado.'], 404);
        }

        $datos = $this->validarClasificador($request);

        $campos = [
            'anio', 'codigo', 'codigo_alias', 'codigo_padre', 'nivel', 'descripcion',
            'es_terminal', 'activo', 'fecha_inicio', 'fecha_fin', 'fuente', 'version_catalogo',
        ];

        $datos = array_merge($clasificador->only($campos), $datos);

        $duplicado = MefClasificadorGasto::where('anio', $datos['anio'])
            ->where('codigo', $datos['codigo'])
            ->where('id', '!=', $clasificador->id)
            ->exists();

        if ($duplicado) {
            return response()->json([
                'message' => "El código {$datos['codigo']} ya existe en el catálogo del ejercicio {$datos['anio']}.",
            ], 422);
        }

        if (!empty($datos['codigo_padre']) && $datos['codigo_padre'] !== $clasificador->codigo_padre) {
            $padreExiste = MefClasificadorGasto::where('anio', $datos['anio'])
                ->where('codigo', $datos['codigo_padre'])
                ->exists();

            if (!$padreExiste) {
                return response()->json([
                    'message' => "El código padre {$datos['codigo_padre']} no existe en el catálogo del ejercicio {$datos['anio']}.",
                ], 422);
            }
        }

        foreach ($campos as $campo) {
            $anterior = $this->texto($clasificador->{$campo});
            $nuevo = $this->texto($datos[$campo] ?? null);

            if ($anterior !== $nuevo) {
                $this->mef->registrarCambio(
                    $clasificador,
                    $campo,
                    $anterior,
                    $nuevo,
                    $datos['fuente'] ?? $clasificador->fuente,
                    $request->user()?->id
                );
            }
        }

        $clasificador->update($datos);

        return response()->json([
            'message' => 'Clasificador actualizado correctamente.',
            'clasificador' => $clasificador->fresh(),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $clasificador = MefClasificadorGasto::find($id);

        if (!$clasificador) {
            return response()->json(['message' => 'Clasificador no encontrado.'], 404);
        }

        if ($clasificador->reglas()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: existen reglas de clasificación que lo utilizan.',
            ], 422);
        }

        $this->mef->registrarCambio(
            $clasificador,
            'eliminado',
            $this->resumen($clasificador),
            null,
            $clasificador->fuente,
            $request->user()?->id
        );

        $clasificador->delete();

        return response()->json(['message' => 'Clasificador eliminado.']);
    }

    public function historial(string $id)
    {
        $clasificador = MefClasificadorGasto::find($id);

        if (!$clasificador) {
            return response()->json(['message' => 'Clasificador no encontrado.'], 404);
        }

        return response()->json($this->mef->historial($clasificador));
    }

    // ========== REGLAS ==========

    public function reglas(Request $request)
    {
        $datos = $request->validate([
            'anio' => 'nullable|integer|min:2000|max:2100',
        ]);

        $anio = (int) ($datos['anio'] ?? now()->year);

        $reglas = MefReglaClasificacionGasto::with('clasificador')
            ->where('anio', $anio)
            ->orderBy('concepto_codigo')
            ->orderBy('prioridad')
            ->get()
            ->map(fn ($regla) => [
                'id' => $regla->id,
                'anio' => $regla->anio,
                'regimen' => $regla->regimen,
                'modalidad' => $regla->modalidad,
                'concepto_codigo' => $regla->concepto_codigo,
                'clasificador_id' => $regla->clasificador_id,
                'codigo' => $regla->clasificador?->codigo,
                'clasificador_descripcion' => $regla->clasificador?->descripcion,
                'prioridad' => $regla->prioridad,
                'activo' => $regla->activo,
                'fuente' => $regla->fuente,
            ]);

        return response()->json($reglas);
    }

    public function storeRegla(Request $request)
    {
        $datos = $this->validarRegla($request);

        if (!MefClasificadorGasto::where('id', $datos['clasificador_id'])
            ->where('anio', $datos['anio'])
            ->exists()) {
            return response()->json([
                'message' => 'El clasificador seleccionado no pertenece al año fiscal de la regla.',
            ], 422);
        }

        $regla = MefReglaClasificacionGasto::create($datos);

        return response()->json([
            'message' => 'Regla creada correctamente.',
            'regla' => $regla->load('clasificador'),
        ], 201);
    }

    public function updateRegla(Request $request, string $id)
    {
        $regla = MefReglaClasificacionGasto::find($id);

        if (!$regla) {
            return response()->json(['message' => 'Regla no encontrada.'], 404);
        }

        $datos = $this->validarRegla($request);

        if (!MefClasificadorGasto::where('id', $datos['clasificador_id'])
            ->where('anio', $datos['anio'])
            ->exists()) {
            return response()->json([
                'message' => 'El clasificador seleccionado no pertenece al año fiscal de la regla.',
            ], 422);
        }

        $regla->update($datos);

        return response()->json([
            'message' => 'Regla actualizada correctamente.',
            'regla' => $regla->fresh('clasificador'),
        ]);
    }

    public function destroyRegla(string $id)
    {
        $regla = MefReglaClasificacionGasto::find($id);

        if (!$regla) {
            return response()->json(['message' => 'Regla no encontrada.'], 404);
        }

        $regla->delete();

        return response()->json(['message' => 'Regla eliminada.']);
    }

    // ========== UTILIDADES ==========

    private function validarClasificador(Request $request): array
    {
        return $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'codigo' => ['required', 'string', 'max:30', 'regex:/^\d+(\.\d+)*$/'],
            'codigo_alias' => ['nullable', 'string', 'max:30', 'regex:/^\d+(\.\d+)*$/'],
            'codigo_padre' => ['nullable', 'string', 'max:30', 'regex:/^\d+(\.\d+)*$/'],
            'nivel' => ['nullable', 'integer', 'min:1', 'max:10'],
            'descripcion' => ['required', 'string', 'max:500'],
            'es_terminal' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'fuente' => ['nullable', 'string', 'max:500'],
            'version_catalogo' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function validarRegla(Request $request): array
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'regimen' => ['nullable', 'string', 'max:50'],
            'modalidad' => ['nullable', 'string', 'max:100'],
            'concepto_codigo' => ['required', 'string', 'max:100'],
            'clasificador_id' => ['required', 'string', 'exists:mef_clasificador_gasto,id'],
            'prioridad' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'activo' => ['nullable', 'boolean'],
            'fuente' => ['nullable', 'string', 'max:500'],
        ]);

        $datos['prioridad'] = $datos['prioridad'] ?? 100;
        $datos['activo'] = $datos['activo'] ?? true;

        return $datos;
    }

    private function resumen(MefClasificadorGasto $clasificador): string
    {
        return "{$clasificador->codigo} — {$clasificador->descripcion}";
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
}
