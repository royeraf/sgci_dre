<?php

namespace App\Http\Controllers;

use App\Models\GratificacionCasParametro;
use App\Services\Planilla\GratificacionCasService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GratificacionController extends Controller
{
    public function getParametros()
    {
        $parametros = GratificacionCasParametro::orderBy('anio_fiscal')->get()->map(fn ($p) => [
            'id' => $p->id,
            'anio_fiscal' => $p->anio_fiscal,
            'porcentaje' => (float) $p->porcentaje,
            'monto_minimo' => $p->monto_minimo !== null ? (float) $p->monto_minimo : null,
            'fecha_vigencia_norma' => $p->fecha_vigencia_norma?->format('Y-m-d'),
            'activo' => $p->activo,
        ]);

        return response()->json($parametros);
    }

    public function updateParametro(Request $request, string $id)
    {
        $parametro = GratificacionCasParametro::find($id);

        if (!$parametro) {
            return response()->json(['message' => 'Parámetro no encontrado'], 404);
        }

        $validated = $request->validate([
            'porcentaje' => 'required|numeric|min:0|max:1',
            'monto_minimo' => 'nullable|numeric|min:0',
            'activo' => 'required|boolean',
        ]);

        $parametro->update($validated);

        return response()->json(['message' => 'Parámetro actualizado correctamente']);
    }

    public function getGratificaciones(Request $request, GratificacionCasService $servicio)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|string|in:JULIO,DICIEMBRE',
        ]);

        try {
            return response()->json($servicio->listado((int) $validated['anio'], $validated['periodo']));
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function previsualizarGratificaciones(Request $request, GratificacionCasService $servicio)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|string|in:JULIO,DICIEMBRE',
        ]);

        try {
            $filas = $servicio->previsualizar((int) $validated['anio'], $validated['periodo']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'rows' => $filas,
            'total' => round(array_sum(array_column($filas, 'monto_final')), 2),
            'total_essalud' => round(array_sum(array_column($filas, 'aporte_essalud')), 2),
        ]);
    }

    public function generarGratificaciones(Request $request, GratificacionCasService $servicio)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|string|in:JULIO,DICIEMBRE',
        ]);

        try {
            $resumen = $servicio->generar((int) $validated['anio'], $validated['periodo'], auth()->id());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(array_merge([
            'message' => 'Gratificaciones generadas correctamente',
        ], $resumen));
    }
}
