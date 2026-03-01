<?php

namespace App\Http\Controllers;

use App\Models\PatrimonioInventario;
use Illuminate\Http\Request;

class PatrimonioInventarioController extends Controller
{
    public function index(Request $request)
    {
        $query = PatrimonioInventario::with(['creadoPor:id,name', 'cerradoPor:id,name'])
            ->orderBy('anio', 'desc')
            ->orderBy('fecha_inicio', 'desc');

        if ($request->filled('anio')) {
            $query->where('anio', $request->anio);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $perPage = $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'anio'        => 'required|integer|min:2000|max:2100',
            'nombre'      => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin'   => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $validated['estado']     = 'PENDIENTE';
        $validated['creado_por'] = auth()->id();

        $inventario = PatrimonioInventario::create($validated);

        return response()->json($inventario->load('creadoPor:id,name'), 201);
    }

    public function update(Request $request, PatrimonioInventario $inventario)
    {
        $validated = $request->validate([
            'nombre'      => 'sometimes|required|string|max:200',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin'   => 'nullable|date|after_or_equal:fecha_inicio',
            'estado'      => 'sometimes|required|in:PENDIENTE,EN_PROCESO,CERRADO',
        ]);

        if (isset($validated['estado'])) {
            if ($validated['estado'] === 'CERRADO' && $inventario->estado !== 'CERRADO') {
                $validated['cerrado_por'] = auth()->id();
                if (empty($validated['fecha_fin'])) {
                    $validated['fecha_fin'] = now()->toDateString();
                }
            }

            if ($validated['estado'] !== 'CERRADO') {
                $validated['cerrado_por'] = null;
            }
        }

        $inventario->update($validated);

        return response()->json($inventario->fresh()->load(['creadoPor:id,name', 'cerradoPor:id,name']));
    }

    public function destroy(PatrimonioInventario $inventario)
    {
        if ($inventario->estado === 'CERRADO') {
            return response()->json(['message' => 'No se puede eliminar un inventario cerrado.'], 422);
        }

        $inventario->delete();

        return response()->json(['message' => 'Inventario eliminado correctamente.']);
    }

    public function stats()
    {
        $total     = PatrimonioInventario::count();
        $porEstado = PatrimonioInventario::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $porAnio = PatrimonioInventario::selectRaw('anio, COUNT(*) as total')
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->limit(5)
            ->get();

        $ultimoAnio = (int) date('Y');

        return response()->json([
            'total'        => $total,
            'pendientes'   => $porEstado['PENDIENTE']  ?? 0,
            'en_proceso'   => $porEstado['EN_PROCESO'] ?? 0,
            'cerrados'     => $porEstado['CERRADO']    ?? 0,
            'por_anio'     => $porAnio,
            'ultimo_anio'  => $ultimoAnio,
        ]);
    }
}
