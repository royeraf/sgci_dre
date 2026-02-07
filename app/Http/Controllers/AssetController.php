<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return \Inertia\Inertia::render('Assets/Index', [
            'categories' => \App\Models\AssetCategory::all(),
        ]);
    }

    public function getAssets(Request $request)
    {
        $query = Asset::with(['category', 'responsible', 'inventariador'])
            ->withCount('movements')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo_patrimonio', 'like', "%{$search}%")
                  ->orWhere('codigo_interno', 'like', "%{$search}%")
                  ->orWhere('codigo_completo', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        
        // Filter by location (exact string match on latest movement?)
        // This is expensive in SQL without denormalization.
        // For now, skip reliable location filtering unless specifically asked or we added location_id to assets.
        
        $perPage = $request->input('per_page', 15);
        
        $assets = $query->paginate($perPage);
        
        // Append location attribute
        $assets->getCollection()->each(function($asset) {
             $asset->append('location');
        });
        
        return response()->json($assets);
    }
    
    public function getStats()
    {
        $total = Asset::count();
        $buenos = Asset::where('estado', 'BUENO')->count();
        $regulares = Asset::where('estado', 'REGULAR')->count();
        $malos = Asset::where('estado', 'MALO')->count();
        
        return response()->json([
            'total' => $total,
            'buenos' => $buenos,
            'regulares' => $regulares,
            'malos' => $malos,
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
            'descripcion' => 'required|string',
            'categoria_id' => 'nullable|exists:asset_categories,id',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'estado' => 'required|string',
            'responsable_id' => 'nullable|exists:asset_responsibles,id',
            'codigo_patrimonio' => 'nullable|string',
            'codigo_interno' => 'nullable|string',
        ]);
        
        // Add inventariador
        $validated['inventariador_id'] = auth()->id();
        
        // Generate codigo_completo
        $code = ($request->codigo_patrimonio ?? '') . ($request->codigo_interno ?? '');
        if ($request->tipo_origen === 'SOBRANTE') {
            $code .= 'S';
        }
        
        // Check if code already exists
        if ($code && Asset::where('codigo_completo', $code)->exists()) {
            return redirect()->back()->withErrors([
                'codigo_patrimonio' => 'El código generado (' . $code . ') ya existe en el sistema.',
                'codigo_interno' => 'El código generado (' . $code . ') ya existe en el sistema.'
            ]);
        }
        
        $validated['codigo_completo'] = $code ?: null;

        $asset = Asset::create(array_merge($validated, $request->except(['ubicacion', 'responsable_nombre', 'codigo_patrimonio', 'codigo_interno', 'codigo_completo']))); 
        
        // Create initial movement
        if ($request->has('responsable_id') || $request->has('ubicacion')) {
             \App\Models\AssetMovement::create([
                 'asset_id' => $asset->id,
                 'tipo' => 'Asignación',
                 'fecha' => $request->fecha_asignacion ?? now(),
                 'ubicacion_actual' => $request->ubicacion,
                 'responsable_id' => $request->responsable_id,
                 'responsable_nombre' => $request->responsable_nombre ?? null, // From frontend text if id is missing?
                 'inventariador_id' => auth()->id(),
                 'estado' => $request->estado,
                 'observaciones' => $request->observacion,
             ]);
        }

        return redirect()->back()->with('success', 'Bien registrado correctamente');
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string',
            'estado' => 'required|string',
        ]);
        
        $asset->update($request->all());
        
        return redirect()->back()->with('success', 'Bien actualizado correctamente');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->back()->with('success', 'Bien eliminado correctamente');
    }
}
