<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetBrand;
use App\Models\AssetColor;
use App\Models\AssetState;
use App\Models\AssetOrigin;
use App\Models\AssetCategory;
use App\Models\AssetMovement;
use App\Models\AssetResponsible;
use App\Models\HrOffice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Assets/Index', [
            'categories' => AssetCategory::all(),
            'brands' => AssetBrand::activas()->orderBy('nombre')->get(),
            'colors' => AssetColor::activos()->orderBy('nombre')->get(),
            'states' => AssetState::activos()->get(),
            'origins' => AssetOrigin::activos()->orderBy('nombre')->get(),
            'offices' => HrOffice::activas()->with('area')->orderBy('nombre')->get(),
        ]);
    }

    public function getAssets(Request $request)
    {
        $query = Asset::with([
                'category', 
                'brand', 
                'color', 
                'origin',
                'latestMovement.state',
                'latestMovement.responsible.employee.person',
                'latestMovement.office.area',
            ])
            ->withCount('movements')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('denominacion', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo_patrimonio', 'like', "%{$search}%")
                  ->orWhere('codigo_interno', 'like', "%{$search}%")
                  ->orWhere('codigo_completo', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%")
                  ->orWhereHas('brand', function($q2) use ($search) {
                      $q2->where('nombre', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filtrar por estado (del último movimiento)
        if ($request->filled('estado_id')) {
            $estadoId = $request->estado_id;
            $query->whereHas('latestMovement', function($q) use ($estadoId) {
                $q->where('estado_id', $estadoId);
            });
        }
        
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('marca_id')) {
            $query->where('marca_id', $request->marca_id);
        }

        // Filtrar por oficina (del último movimiento)
        if ($request->filled('oficina_id')) {
            $oficinaId = $request->oficina_id;
            $query->whereHas('latestMovement', function($q) use ($oficinaId) {
                $q->where('oficina_id', $oficinaId);
            });
        }
        
        $perPage = $request->input('per_page', 15);
        
        $assets = $query->paginate($perPage);
        
        return response()->json($assets);
    }
    
    public function getStats()
    {
        $total = Asset::count();
        
        // Contar por estado del último movimiento
        $estados = AssetState::withCount(['movements' => function($q) {
            // Solo contar movimientos que son el último de cada asset
            $q->whereIn('id', function($subquery) {
                $subquery->selectRaw('MAX(id)')
                    ->from('asset_movements')
                    ->groupBy('asset_id');
            });
        }])->get();
        
        $statsByState = [];
        foreach ($estados as $estado) {
            $statsByState[strtolower($estado->nombre)] = $estado->movements_count;
        }
        
        return response()->json([
            'total' => $total,
            'by_state' => $statsByState,
            // Para compatibilidad temporal
            'buenos' => $statsByState['bueno'] ?? 0,
            'regulares' => $statsByState['regular'] ?? 0,
            'malos' => $statsByState['malo'] ?? 0,
        ]);
    }

    public function checkCode(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return response()->json(['exists' => false]);
        }

        $exists = Asset::where('codigo_completo', $code)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_patrimonio' => 'required|string|size:8',
            'codigo_interno' => 'required|string|size:4',
            'denominacion' => 'required|string|min:3|max:200',
            'descripcion' => 'nullable|string|max:500',
            'categoria_id' => 'nullable|exists:asset_categories,id',
            'marca_id' => 'nullable|exists:asset_brands,id',
            'color_id' => 'nullable|exists:asset_colors,id',
            'origen_id' => 'nullable|exists:asset_origins,id',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'dimension' => 'nullable|string|max:100',
            'fecha_adquisicion' => 'nullable|date',
            'observacion' => 'nullable|string',
            'codigo_barras' => 'nullable|string|max:50',
            // Datos del movimiento inicial
            'estado_id' => 'required|exists:asset_states,id',
            'oficina_id' => 'nullable|exists:hr_offices,id',
            'responsable_id' => 'nullable|exists:asset_responsibles,id',
            'responsable_nombre' => 'nullable|string|max:200',
            'fecha_asignacion' => 'nullable|date',
        ]);
        
        // Generate codigo_completo
        $code = $request->codigo_patrimonio . $request->codigo_interno;
        
        // Check if code already exists
        if (Asset::where('codigo_completo', $code)->exists()) {
            return redirect()->back()->withErrors([
                'codigo_patrimonio' => 'El código generado (' . $code . ') ya existe en el sistema.',
                'codigo_interno' => 'El código generado (' . $code . ') ya existe en el sistema.'
            ]);
        }
        
        // Crear o buscar responsable si se proporciona nombre
        $responsableId = $request->responsable_id;
        if (!$responsableId && $request->filled('responsable_nombre')) {
            $responsible = AssetResponsible::firstOrCreate(
                ['nombre_original' => strtoupper(trim($request->responsable_nombre))],
                ['activo' => true]
            );
            $responsableId = $responsible->id;
        }

        // Crear el asset
        $asset = Asset::create([
            'codigo_patrimonio' => $request->codigo_patrimonio,
            'codigo_interno' => strtoupper($request->codigo_interno),
            'codigo_completo' => $code,
            'denominacion' => $request->denominacion,
            'descripcion' => $request->descripcion,
            'categoria_id' => $request->categoria_id,
            'marca_id' => $request->marca_id,
            'color_id' => $request->color_id,
            'origen_id' => $request->origen_id,
            'modelo' => $request->modelo,
            'numero_serie' => $request->numero_serie,
            'dimension' => $request->dimension,
            'fecha_adquisicion' => $request->fecha_adquisicion,
            'observacion' => $request->observacion,
            'codigo_barras' => $request->codigo_barras,
        ]); 
        
        // Crear movimiento inicial (siempre)
        AssetMovement::create([
            'asset_id' => $asset->id,
            'tipo' => 'ASIGNACION',
            'fecha' => $request->fecha_asignacion ?? now()->toDateString(),
            'estado_id' => $request->estado_id,
            'oficina_id' => $request->oficina_id,
            'responsable_id' => $responsableId,
            'inventariador_id' => auth()->id(),
            'observaciones' => $request->observacion,
        ]);

        return redirect()->back()->with('success', 'Bien registrado correctamente');
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'denominacion' => 'required|string|min:3|max:200',
            'descripcion' => 'nullable|string|max:500',
            'categoria_id' => 'nullable|exists:asset_categories,id',
            'marca_id' => 'nullable|exists:asset_brands,id',
            'color_id' => 'nullable|exists:asset_colors,id',
            'origen_id' => 'nullable|exists:asset_origins,id',
            'modelo' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'dimension' => 'nullable|string|max:100',
        ]);
        
        $asset->update($validated);
        
        return redirect()->back()->with('success', 'Bien actualizado correctamente');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->back()->with('success', 'Bien eliminado correctamente');
    }

    /**
     * Registrar un nuevo movimiento para un activo
     */
    public function storeMovement(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:ASIGNACION,DEVOLUCION,TRASLADO,BAJA',
            'fecha' => 'required|date',
            'estado_id' => 'required|exists:asset_states,id',
            'oficina_id' => 'nullable|exists:hr_offices,id',
            'responsable_id' => 'nullable|exists:asset_responsibles,id',
            'observaciones' => 'nullable|string',
        ]);

        $validated['asset_id'] = $asset->id;
        $validated['inventariador_id'] = auth()->id();

        AssetMovement::create($validated);

        return redirect()->back()->with('success', 'Movimiento registrado correctamente');
    }
}
