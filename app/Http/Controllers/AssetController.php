<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetBrand;
use App\Models\Employee;
use App\Models\AssetColor;
use App\Models\AssetState;
use App\Models\AssetOrigin;
use App\Models\AssetCategory;
use App\Models\AssetMovement;
use App\Models\AssetResponsible;
use App\Models\HrDirection;
use App\Models\HrOffice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;

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
            'areas' => HrDirection::activas()->orderBy('nombre')->get(),
            'offices' => HrOffice::activas()->with('direction')->orderBy('nombre')->get(),
            'employees' => Employee::with('person')
                ->where('estado', 'ACTIVO')
                ->whereHas('person', function($q) {
                    $q->whereNotNull('nombres')
                      ->whereNotNull('apellidos')
                      ->where('nombres', '!=', '')
                      ->where('apellidos', '!=', '');
                })
                ->get()
                ->map(function($emp) {
                    return [
                        'id' => $emp->id,
                        'nombre_completo' => trim($emp->full_name),
                        'dni' => $emp->dni,
                    ];
                })
                ->sortBy('nombre_completo')
                ->values(),
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
                'latestMovement.office.direction',
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
                  ->orWhere('codigo_barras', 'like', "%{$search}%")
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
            'area_id' => 'nullable|exists:hr_directions,id',
            'oficina_id' => 'nullable|exists:hr_offices,id',
            'employee_id' => 'nullable|exists:employees,id',
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

        // Crear o buscar responsable vinculado al empleado
        $responsableId = null;
        if ($request->filled('employee_id')) {
            $employee = Employee::with('person')->find($request->employee_id);
            if ($employee) {
                $responsible = AssetResponsible::firstOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'nombre_original' => strtoupper($employee->full_name),
                        'activo' => true,
                    ]
                );
                $responsableId = $responsible->id;
            }
        }

        // Crear el asset con código de barras auto-generado
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
            'codigo_barras' => $code,
        ]); 
        
        // Crear movimiento inicial (siempre)
        AssetMovement::create([
            'asset_id' => $asset->id,
            'tipo' => 'ASIGNACION',
            'fecha' => $request->fecha_asignacion ?? now()->toDateString(),
            'estado_id' => $request->estado_id,
            'area_id' => $request->area_id,
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
            'fecha_adquisicion' => 'nullable|date',
            'observacion' => 'nullable|string',
        ]);
        
        $asset->update($validated);
        
        return redirect()->back()->with('success', 'Bien actualizado correctamente');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return response()->json([
            'message' => 'Bien eliminado correctamente'
        ]);
    }

    /**
     * Generar PDF con etiquetas de códigos de barra
     * Soporta individual (?ids=1) y lote (?ids=1,2,3)
     */
    public function generateBarcodePdf(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        $size = $request->query('size', 'medium');

        $assets = Asset::whereIn('id', $ids)->get();

        if ($assets->isEmpty()) {
            abort(404, 'No se encontraron activos');
        }

        // Generar codigo_barras si no existe y generar PNG base64 para cada activo
        $generator = new BarcodeGeneratorPNG();
        $barcodes = [];

        foreach ($assets as $asset) {
            if (!$asset->codigo_barras) {
                $asset->update(['codigo_barras' => $asset->codigo_completo]);
            }

            $code = $asset->codigo_barras;
            $png = $generator->getBarcode($code, $generator::TYPE_CODE_128, 4, 100);
            $barcodes[$asset->id] = 'data:image/png;base64,' . base64_encode($png);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.etiquetas_codigos_barra', [
            'assets' => $assets,
            'barcodes' => $barcodes,
            'size' => $size,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('etiquetas_codigos_barra.pdf');
    }

    /**
     * Listar movimientos con filtros y paginación
     */
    public function getMovements(Request $request)
    {
        $query = AssetMovement::with([
                'asset.category',
                'asset.brand',
                'responsible.employee.person',
                'state',
                'office.direction',
                'direction',
                'inventariador',
            ])
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc');

        // Búsqueda por código o denominación del activo
        if ($request->filled('search')) {
            $search = $request->search;

            // Primero intentar buscar coincidencia exacta de código para ser más precisos con el escáner
            $exactAsset = \App\Models\Asset::where('codigo_barras', $search)
                ->orWhere('codigo_patrimonio', $search)
                ->orWhere('codigo_completo', $search)
                ->first();

            if ($exactAsset) {
                $query->where('asset_id', $exactAsset->id);
            } else {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('asset', function ($q2) use ($search) {
                        $q2->where('codigo_patrimonio', 'like', "%{$search}%")
                            ->orWhere('codigo_interno', 'like', "%{$search}%")
                            ->orWhere('codigo_completo', 'like', "%{$search}%")
                            ->orWhere('codigo_barras', 'like', "%{$search}%")
                            ->orWhere('denominacion', 'like', "%{$search}%");
                    })
                        ->orWhere('observaciones', 'like', "%{$search}%")
                        ->orWhereHas('responsible', function ($q2) use ($search) {
                            $q2->where('nombre_original', 'like', "%{$search}%")
                                ->orWhereHas('employee.person', function ($q3) use ($search) {
                                    $q3->where('nombres', 'like', "%{$search}%")
                                        ->orWhere('apellidos', 'like', "%{$search}%")
                                        ->orWhere('apellido_paterno', 'like', "%{$search}%");
                                });
                        });
                });
            }
        }

        // Filtrar por tipo de movimiento
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        // Filtrar por estado
        if ($request->filled('estado_id')) {
            $query->where('estado_id', $request->estado_id);
        }

        // Filtrar por oficina
        if ($request->filled('oficina_id')) {
            $query->where('oficina_id', $request->oficina_id);
        }

        // Filtrar por rango de fechas
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $perPage = $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Resumen de estadísticas de movimientos
     */
    public function getMovementStats()
    {
        $total = AssetMovement::count();
        
        $byType = AssetMovement::selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();

        $lastMonth = AssetMovement::where('fecha', '>=', now()->subMonth())->count();

        return response()->json([
            'total' => $total,
            'asignaciones' => $byType['ASIGNACION'] ?? 0,
            'devoluciones' => $byType['DEVOLUCION'] ?? 0,
            'traslados' => $byType['TRASLADO'] ?? 0,
            'bajas' => $byType['BAJA'] ?? 0,
            'ultimo_mes' => $lastMonth,
        ]);
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

        $movement = AssetMovement::create($validated);

        if ($request->wantsJson()) {
            return response()->json($movement->load(['state', 'responsible', 'office']), 201);
        }

        return redirect()->back()->with('success', 'Movimiento registrado correctamente');
    }

    /**
     * Generar reporte de bienes asignados a un responsable
     */
    public function reportByResponsible($responsibleId)
    {
        $responsible = AssetResponsible::with(['employee.person', 'direction', 'office'])
            ->findOrFail($responsibleId);

        // Buscar bienes asignados actualmente a este responsable (último movimiento lo tiene a él como responsable)
        $assets = Asset::whereHas('latestMovement', function ($query) use ($responsibleId) {
            $query->where('responsable_id', $responsibleId);
        })->with(['brand', 'latestMovement.state'])->get();

        $pdf = Pdf::loadView('pdf.bienes_asignados', compact('responsible', 'assets'));
        
        return $pdf->stream('bienes_asignados_' . $responsible->employee->dni . '.pdf');
    }
}
