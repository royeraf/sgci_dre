<?php

namespace App\Http\Controllers;

use App\Models\AssetResponsible;
use App\Models\Employee;
use App\Models\PatrimonioInventario;
use App\Models\PatrimonioInventarioItem;
use Illuminate\Http\Request;

class PatrimonioInventarioController extends Controller
{
    // ===== INVENTARIOS =====

    public function index(Request $request)
    {
        $query = PatrimonioInventario::with(['creadoPor:id,name', 'cerradoPor:id,name'])
            ->withCount('items')
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
            'nombre'       => 'sometimes|required|string|max:200',
            'descripcion'  => 'nullable|string',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'estado'       => 'sometimes|required|in:PENDIENTE,EN_PROCESO,CERRADO',
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
        $total    = PatrimonioInventario::count();
        $porEstado = PatrimonioInventario::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $porAnio = PatrimonioInventario::selectRaw('anio, COUNT(*) as total')
            ->groupBy('anio')
            ->orderBy('anio', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total'       => $total,
            'pendientes'  => $porEstado['PENDIENTE']  ?? 0,
            'en_proceso'  => $porEstado['EN_PROCESO'] ?? 0,
            'cerrados'    => $porEstado['CERRADO']    ?? 0,
            'por_anio'    => $porAnio,
            'ultimo_anio' => (int) date('Y'),
        ]);
    }

    // ===== ITEMS (VERIFICACIONES) =====

    public function getItems(Request $request, PatrimonioInventario $inventario)
    {
        $query = $inventario->items()
            ->with([
                'asset:id,codigo_patrimonio,codigo_interno,codigo_completo,denominacion,marca_id,categoria_id',
                'asset.brand:id,nombre',
                'estado:id,nombre',
                'oficina:id,nombre',
                'responsable:id,nombre_original,employee_id',
                'responsable.employee:id,dni',
                'responsable.employee.person:id,nombres,apellido_paterno,apellido_materno',
                'inventariador:id,name',
            ])
            ->orderBy('fecha_verificacion', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('asset', function ($q) use ($search) {
                $q->where('denominacion', 'like', "%{$search}%")
                  ->orWhere('codigo_patrimonio', 'like', "%{$search}%")
                  ->orWhere('codigo_completo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        $perPage = $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function itemStats(PatrimonioInventario $inventario)
    {
        $total = $inventario->items()->count();

        $porEstado = $inventario->items()
            ->join('asset_states', 'asset_states.id', '=', 'patrimonio_inventario_items.estado_id')
            ->selectRaw('asset_states.nombre as estado, COUNT(*) as total')
            ->groupBy('asset_states.nombre')
            ->pluck('total', 'estado')
            ->toArray();

        return response()->json([
            'total'     => $total,
            'por_estado' => $porEstado,
        ]);
    }

    public function storeItem(Request $request, PatrimonioInventario $inventario)
    {
        $validated = $request->validate([
            'asset_id'           => 'required|exists:assets,id',
            'estado_id'          => 'nullable|exists:asset_states,id',
            'oficina_id'         => 'nullable|exists:hr_offices,id',
            'employee_id'        => 'nullable|exists:employees,id',
            'observaciones'      => 'nullable|string',
            'fecha_verificacion' => 'required|date',
        ]);

        // Resolver o crear el responsable desde el empleado seleccionado
        $responsableId = null;
        if (!empty($validated['employee_id'])) {
            $employee = Employee::with('person')->find($validated['employee_id']);
            if ($employee) {
                $responsible = AssetResponsible::firstOrCreate(
                    ['employee_id' => $employee->id],
                    ['nombre_original' => strtoupper($employee->full_name), 'activo' => true]
                );
                $responsableId = $responsible->id;
            }
        }

        $item = PatrimonioInventarioItem::updateOrCreate(
            [
                'inventario_id' => $inventario->id,
                'asset_id'      => $validated['asset_id'],
            ],
            [
                'estado_id'          => $validated['estado_id'] ?? null,
                'oficina_id'         => $validated['oficina_id'] ?? null,
                'responsable_id'     => $responsableId,
                'observaciones'      => $validated['observaciones'] ?? null,
                'inventariador_id'   => auth()->id(),
                'fecha_verificacion' => $validated['fecha_verificacion'],
            ]
        );

        return response()->json(
            $item->fresh()->load([
                'asset:id,codigo_patrimonio,codigo_interno,codigo_completo,denominacion',
                'estado:id,nombre',
                'oficina:id,nombre',
                'responsable:id,nombre_original,employee_id',
                'responsable.employee.person:id,nombres,apellido_paterno',
                'inventariador:id,name',
            ]),
            201
        );
    }

    public function destroyItem(PatrimonioInventario $inventario, PatrimonioInventarioItem $item)
    {
        if ($item->inventario_id !== $inventario->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $item->delete();

        return response()->json(['message' => 'Verificación eliminada correctamente.']);
    }
}
