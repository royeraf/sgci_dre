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
use App\Models\AssetMovementType;
use App\Models\AssetResponsible;
use App\Models\PatrimonioAsset;
use App\Models\HrDirection;
use App\Models\HrOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Barryvdh\DomPDF\Facade\Pdf;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $myEmployee = $user->employee?->load('person');

        return Inertia::render('Assets/Index', [
            'myEmployee'    => $myEmployee ? ['id' => $myEmployee->id, 'nombre_completo' => $myEmployee->full_name] : null,
            'categories'    => AssetCategory::all(),
            'brands'        => AssetBrand::activas()->orderBy('nombre')->get(),
            'colors'        => AssetColor::activos()->orderBy('nombre')->get(),
            'states'        => AssetState::activos()->get(),
            'origins'       => AssetOrigin::activos()->orderBy('nombre')->get(),
            'movementTypes' => AssetMovementType::activos()->get(),
            'areas'         => HrDirection::activas()->orderBy('nombre')->get(),
            'offices'       => HrOffice::activas()->with('direction')->orderBy('nombre')->get(),
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
                'patrimonioAsset:id,asset_id,sincronizado,fecha_sincronizacion',
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
    
    /**
     * API: bienes asignados al empleado autenticado (del último movimiento).
     */
    public function getMisBienes(Request $request)
    {
        $employee = Auth::user()->employee;

        if (!$employee) {
            return response()->json(['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1]);
        }

        $responsableIds = AssetResponsible::where('employee_id', $employee->id)->pluck('id');

        $query = Asset::with([
                'category',
                'brand',
                'latestMovement.state',
                'latestMovement.office',
            ])
            ->whereHas('latestMovement', function ($q) use ($responsableIds) {
                $q->whereIn('responsable_id', $responsableIds);
            })
            ->orderBy('denominacion');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('denominacion', 'like', "%{$s}%")
                  ->orWhere('codigo_patrimonio', 'like', "%{$s}%")
                  ->orWhere('codigo_interno', 'like', "%{$s}%")
                  ->orWhere('numero_serie', 'like', "%{$s}%");
            });
        }

        $perPage = $request->input('per_page', 50);
        return response()->json($query->paginate($perPage));
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

    public function lookupSbnCatalog(Request $request)
    {
        $code = $request->query('code');
        if (!$code || strlen($code) !== 8) {
            return response()->json(['found' => false]);
        }

        $item = \App\Models\SbnCatalogItem::where('codigo', $code)->first();
        if (!$item) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'denominacion' => $item->denominacion,
            'grupo_generico' => $item->grupo_generico,
            'clase' => $item->clase,
        ]);
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
     * Buscar un activo por código de barras (para el carrito de asignación masiva)
     */
    public function lookupByBarcode(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return response()->json(['found' => false]);
        }

        $asset = Asset::with([
                'brand',
                'latestMovement.state',
            ])
            ->where(function ($q) use ($code) {
                $q->where('codigo_barras', $code)
                  ->orWhere('codigo_completo', $code)
                  ->orWhere('codigo_patrimonio', $code);
            })
            ->first();

        if (!$asset) {
            return response()->json(['found' => false]);
        }

        return response()->json(['found' => true, 'asset' => $asset]);
    }

    /**
     * Registrar movimientos en lote (carrito de asignación masiva)
     */
    public function storeBatchMovements(Request $request)
    {
        $validated = $request->validate([
            'tipo_movimiento_id' => 'required|exists:asset_movement_types,id',
            'fecha'              => 'required|date',
            'oficina_id'         => 'nullable|exists:hr_offices,id',
            'responsable_id'     => 'nullable|exists:asset_responsibles,id',
            'observaciones'      => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.asset_id'   => 'required|exists:assets,id',
            'items.*.estado_id'  => 'required|exists:asset_states,id',
        ]);

        $movementType    = AssetMovementType::findOrFail($validated['tipo_movimiento_id']);
        $inventariadorId = auth()->id();
        $created         = 0;

        foreach ($validated['items'] as $item) {
            AssetMovement::create([
                'asset_id'           => $item['asset_id'],
                'tipo'               => $movementType->codigo,
                'tipo_movimiento_id' => $movementType->id,
                'fecha'              => $validated['fecha'],
                'estado_id'          => $item['estado_id'],
                'oficina_id'         => $validated['oficina_id'] ?? null,
                'responsable_id'     => $validated['responsable_id'] ?? null,
                'observaciones'      => $validated['observaciones'] ?? null,
                'inventariador_id'   => $inventariadorId,
            ]);
            $created++;
        }

        return response()->json([
            'message' => "Se registraron {$created} movimientos correctamente.",
            'created' => $created,
        ], 201);
    }

    /**
     * Registrar un nuevo movimiento para un activo
     */
    public function storeMovement(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'tipo_movimiento_id' => 'required|exists:asset_movement_types,id',
            'fecha'              => 'required|date',
            'estado_id'          => 'required|exists:asset_states,id',
            'oficina_id'         => 'nullable|exists:hr_offices,id',
            'responsable_id'     => 'nullable|exists:asset_responsibles,id',
            'observaciones'      => 'nullable|string',
        ]);

        $movementType = AssetMovementType::findOrFail($validated['tipo_movimiento_id']);
        $validated['tipo']     = $movementType->codigo;
        $validated['asset_id'] = $asset->id;
        $validated['inventariador_id'] = auth()->id();

        $movement = AssetMovement::create($validated);

        if ($request->wantsJson()) {
            return response()->json($movement->load(['state', 'responsible', 'office']), 201);
        }

        return redirect()->back()->with('success', 'Movimiento registrado correctamente');
    }

    /**
     * Generar reporte Excel de bienes asignados a un responsable
     */
    public function reportByResponsible($responsibleId)
    {
        $responsible = AssetResponsible::with(['employee.person', 'direction', 'office'])
            ->findOrFail($responsibleId);

        // Buscar todos los IDs de responsable que coincidan (por ID directo + por nombre similar)
        $responsibleIds = collect([$responsibleId]);
        $nombreResp = mb_strtoupper(trim($responsible->nombre_original ?? ''));
        if ($nombreResp) {
            $similarIds = AssetResponsible::whereRaw('UPPER(TRIM(nombre_original)) = ?', [$nombreResp])
                ->pluck('id');
            $responsibleIds = $responsibleIds->merge($similarIds)->unique();
        }

        // Buscar assets cuyo movimiento más reciente CON responsable sea alguno de estos responsables
        $assets = Asset::whereHas('movements', function ($query) use ($responsibleIds) {
            $query->whereIn('responsable_id', $responsibleIds)
                ->whereIn('id', function ($sub) {
                    $sub->selectRaw('MAX(id)')
                        ->from('asset_movements')
                        ->whereNotNull('responsable_id')
                        ->groupBy('asset_id');
                });
        })->with([
            'brand',
            'color',
            'latestMovement.state',
            'latestMovement.office',
            'movements' => function ($q) {
                $q->whereNotNull('responsable_id')->latest('id')->limit(1);
            },
            'movements.state',
            'movements.office',
        ])->orderBy('denominacion')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bienes Asignados');

        // Configurar página A4 vertical
        $sheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);

        // Estilos
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $colHeaderStyle = [
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D3748']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $dataCellStyle = [
            'font' => ['size' => 8],
            'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E0']]],
        ];

        // Encabezado institucional
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', 'DIRECCIÓN REGIONAL DE EDUCACIÓN HUÁNUCO');
        $sheet->getStyle('A1')->applyFromArray($headerStyle);

        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'REPORTE DE BIENES ASIGNADOS');
        $sheet->getStyle('A2')->applyFromArray($subHeaderStyle);

        // Info del responsable
        $nombreResponsable = strtoupper($responsible->full_name);
        $dniResponsable = $responsible->dni ?? '';
        $areaResponsable = $responsible->area_office ?? 'NO ESPECIFICADO';

        $sheet->mergeCells('A4:J4');
        $sheet->setCellValue('A4', "Responsable: {$nombreResponsable}    DNI: {$dniResponsable}    Oficina/Área: {$areaResponsable}");
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(9);

        $sheet->mergeCells('A5:J5');
        $sheet->setCellValue('A5', 'Generado el: ' . now()->format('d/m/Y H:i A'));
        $sheet->getStyle('A5')->getFont()->setSize(8)->setItalic(true);

        // Cabeceras de columna
        $row = 7;
        $columns = ['A' => 'Item', 'B' => 'Código del Bien', 'C' => 'Código Interno', 'D' => 'Detalle del Bien', 'E' => 'Características', 'F' => 'Oficina', 'G' => 'Estado', 'H' => 'Cant.', 'I' => 'Cód. Anterior', 'J' => 'Cuenta Contable', 'K' => 'Importe'];

        foreach ($columns as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }
        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray($colHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(5);   // Item
        $sheet->getColumnDimension('B')->setWidth(12);  // Código
        $sheet->getColumnDimension('C')->setWidth(8);   // Cód. Interno
        $sheet->getColumnDimension('D')->setWidth(25);  // Detalle
        $sheet->getColumnDimension('E')->setWidth(22);  // Características
        $sheet->getColumnDimension('F')->setWidth(18);  // Oficina
        $sheet->getColumnDimension('G')->setWidth(6);   // Estado
        $sheet->getColumnDimension('H')->setWidth(6);   // Cant.
        $sheet->getColumnDimension('I')->setWidth(10);  // Cód. Anterior
        $sheet->getColumnDimension('J')->setWidth(12);  // Cuenta Contable
        $sheet->getColumnDimension('K')->setWidth(10);  // Importe

        // Datos
        $dataRow = $row + 1;
        $estadoMap = ['BUENO' => 'B', 'REGULAR' => 'R', 'MALO' => 'M', 'BAJA' => 'BJ', 'EN REPARACIÓN' => 'ER', 'PERDIDO' => 'P'];

        foreach ($assets as $index => $asset) {
            // Usar el movimiento con responsable (más preciso) o el latestMovement como fallback
            $movConResp = $asset->movements->first();
            $movRef = $movConResp ?? $asset->latestMovement;

            $estadoNombre = optional($movRef?->state)->nombre ?? '';
            $estadoAbrev = $estadoMap[mb_strtoupper($estadoNombre)] ?? $estadoNombre;

            // Construir características: marca, modelo, serie, color, dimensión
            $caract = [];
            if ($asset->brand?->nombre) $caract[] = $asset->brand->nombre;
            if ($asset->modelo) $caract[] = $asset->modelo;
            if ($asset->numero_serie) $caract[] = "S/N: {$asset->numero_serie}";
            if ($asset->color?->nombre) $caract[] = $asset->color->nombre;
            if ($asset->dimension) $caract[] = $asset->dimension;
            $caracteristicas = implode(', ', $caract);

            $oficina = $movRef?->office?->nombre ?? '';

            $sheet->setCellValue("A{$dataRow}", $index + 1);
            $sheet->setCellValueExplicit("B{$dataRow}", $asset->codigo_patrimonio, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$dataRow}", $asset->codigo_interno, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$dataRow}", $asset->denominacion);
            $sheet->setCellValue("E{$dataRow}", $caracteristicas);
            $sheet->setCellValue("F{$dataRow}", $oficina);
            $sheet->setCellValue("G{$dataRow}", $estadoAbrev);
            $sheet->setCellValue("H{$dataRow}", 1); // Cant.
            $sheet->setCellValue("I{$dataRow}", ''); // Cód. Anterior - vacío
            $sheet->setCellValue("J{$dataRow}", ''); // Cuenta Contable - vacío
            $sheet->setCellValue("K{$dataRow}", ''); // Importe - vacío

            $sheet->getStyle("A{$dataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$dataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$dataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $dataRow++;
        }

        // Estilo a todo el rango de datos
        $lastDataRow = $dataRow - 1;
        if ($lastDataRow >= $row + 1) {
            $sheet->getStyle("A" . ($row + 1) . ":K{$lastDataRow}")->applyFromArray($dataCellStyle);
        }

        // Total al final
        $sheet->mergeCells("A{$dataRow}:G{$dataRow}");
        $sheet->setCellValue("A{$dataRow}", "Total de bienes: " . count($assets));
        $sheet->getStyle("A{$dataRow}:K{$dataRow}")->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle("A{$dataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue("H{$dataRow}", count($assets));
        $sheet->getStyle("H{$dataRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // ===== SECCIÓN DE FIRMAS (3 personas) =====
        $signLabelStyle = [
            'font' => ['bold' => true, 'size' => 8],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $signSubStyle = [
            'font' => ['size' => 7, 'italic' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $signRow = $dataRow + 4;

        // Líneas de firma (row $signRow)
        foreach (['A', 'E', 'I'] as $startCol) {
            $endCol = ['A' => 'C', 'E' => 'G', 'I' => 'K'][$startCol];
            $sheet->mergeCells("{$startCol}{$signRow}:{$endCol}{$signRow}");
            $sheet->setCellValue("{$startCol}{$signRow}", '____________________________');
            $sheet->getStyle("{$startCol}{$signRow}")->applyFromArray($signLabelStyle);
        }

        // Etiquetas de cargo (row $signRow + 1)
        $labelRow = $signRow + 1;
        $sheet->mergeCells("A{$labelRow}:C{$labelRow}");
        $sheet->setCellValue("A{$labelRow}", 'Jefe/a de Patrimonio');
        $sheet->getStyle("A{$labelRow}")->applyFromArray($signLabelStyle);

        $sheet->mergeCells("E{$labelRow}:G{$labelRow}");
        $sheet->setCellValue("E{$labelRow}", strtoupper($nombreResponsable));
        $sheet->getStyle("E{$labelRow}")->applyFromArray($signLabelStyle);

        $sheet->mergeCells("I{$labelRow}:K{$labelRow}");
        $sheet->setCellValue("I{$labelRow}", 'Inventariador');
        $sheet->getStyle("I{$labelRow}")->applyFromArray($signLabelStyle);

        // Sub-etiquetas (row $signRow + 2)
        $subRow = $signRow + 2;
        $sheet->mergeCells("A{$subRow}:C{$subRow}");
        $sheet->setCellValue("A{$subRow}", 'Dirección Regional de Educación Huánuco');
        $sheet->getStyle("A{$subRow}")->applyFromArray($signSubStyle);

        $sheet->mergeCells("E{$subRow}:G{$subRow}");
        $sheet->setCellValue("E{$subRow}", "DNI: {$dniResponsable}   —   Recibí Conforme");
        $sheet->getStyle("E{$subRow}")->applyFromArray($signSubStyle);

        $sheet->mergeCells("I{$subRow}:K{$subRow}");
        $sheet->setCellValue("I{$subRow}", 'Control Patrimonial');
        $sheet->getStyle("I{$subRow}")->applyFromArray($signSubStyle);

        // Generar respuesta
        $fileName = 'bienes_asignados_' . ($responsible->employee?->dni ?? $responsibleId) . '.xlsx';

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Vista previa PDF de bienes asignados a un responsable
     */
    public function reportByResponsiblePdf($responsibleId)
    {
        $responsible = AssetResponsible::with(['employee.person', 'direction', 'office'])
            ->findOrFail($responsibleId);

        $responsibleIds = collect([$responsibleId]);
        $nombreResp = mb_strtoupper(trim($responsible->nombre_original ?? ''));
        if ($nombreResp) {
            $similarIds = AssetResponsible::whereRaw('UPPER(TRIM(nombre_original)) = ?', [$nombreResp])
                ->pluck('id');
            $responsibleIds = $responsibleIds->merge($similarIds)->unique();
        }

        $assets = Asset::whereHas('movements', function ($query) use ($responsibleIds) {
            $query->whereIn('responsable_id', $responsibleIds)
                ->whereIn('id', function ($sub) {
                    $sub->selectRaw('MAX(id)')
                        ->from('asset_movements')
                        ->whereNotNull('responsable_id')
                        ->groupBy('asset_id');
                });
        })->with([
            'brand',
            'color',
            'latestMovement.state',
            'latestMovement.office',
        ])->orderBy('denominacion')->get();

        $pdf = Pdf::loadView('pdf.bienes_asignados', [
            'responsible' => $responsible,
            'assets' => $assets,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('preview_bienes_' . ($responsible->employee?->dni ?? $responsibleId) . '.pdf');
    }

    // ===== PATRIMONIO SIGA =====

    public function getPatrimonioAssets(Request $request)
    {
        $query = PatrimonioAsset::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo_patrimonial', 'like', "%{$search}%")
                  ->orWhere('denominacion', 'like', "%{$search}%")
                  ->orWhere('responsable_final', 'like', "%{$search}%")
                  ->orWhere('oficina', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('nro_serie', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado_conserv')) {
            $query->where('estado_conserv', $request->estado_conserv);
        }

        if ($request->filled('lote_importacion')) {
            $query->where('lote_importacion', $request->lote_importacion);
        }

        if ($request->filled('sincronizado')) {
            $query->where('sincronizado', $request->sincronizado === 'true');
        }

        $perPage = $request->input('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function getPatrimonioStats()
    {
        $total = PatrimonioAsset::count();
        $sincronizados = PatrimonioAsset::where('sincronizado', true)->count();
        $noSincronizados = $total - $sincronizados;

        $lotes = PatrimonioAsset::selectRaw('lote_importacion, archivo_origen, fecha_importacion, COUNT(*) as total')
            ->groupBy('lote_importacion', 'archivo_origen', 'fecha_importacion')
            ->orderBy('fecha_importacion', 'desc')
            ->limit(10)
            ->get();

        $porEstado = PatrimonioAsset::selectRaw('estado_conserv, COUNT(*) as total')
            ->groupBy('estado_conserv')
            ->pluck('total', 'estado_conserv')
            ->toArray();

        return response()->json([
            'total' => $total,
            'sincronizados' => $sincronizados,
            'no_sincronizados' => $noSincronizados,
            'lotes' => $lotes,
            'por_estado' => $porEstado,
        ]);
    }

    public function importPatrimonio(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $fileName = $file->getClientOriginalName();
        $lote = Str::uuid()->toString();

        try {
            $reader = IOFactory::createReader('Csv');
            $reader->setReadDataOnly(true);
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');
            $spreadsheet = $reader->load($filePath);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al leer el archivo: ' . $e->getMessage()], 422);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $totalRows = $highestRow - 1;

        // Leer cabeceras para mapear columnas dinámicamente
        $headers = [];
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $headers[trim($sheet->getCell("{$colLetter}1")->getValue())] = $colLetter;
        }

        $importedCount = 0;
        $errorsCount = 0;
        $errors = [];

        // Mapeo de columnas CSV → campos de BD
        $columnMap = [
            'CODIGO_PATRIMONIAL' => 'codigo_patrimonial',
            'CODIGO_INTERNO' => 'codigo_interno',
            'CODIGO_ACTIVO' => 'codigo_activo',
            'DENOMINACION' => 'denominacion',
            'DESCRIPCION' => 'descripcion',
            'CARACTERISTICAS' => 'caracteristicas',
            'OBSERVACIONES' => 'observaciones',
            'MARCA' => 'marca',
            'MODELO' => 'modelo',
            'NRO_SERIE' => 'nro_serie',
            'MEDIDAS' => 'medidas',
            'ESTADO_CONSERV' => 'estado_conserv',
            'ESTADO_DESC' => 'estado_desc',
            'RESPONSABLE_FINAL' => 'responsable_final',
            'EMPLEADO_FINAL' => 'empleado_final',
            'OFICINA' => 'oficina',
            'FECHA_COMPRA' => 'fecha_compra',
            'FECHA_ALTA' => 'fecha_alta',
            'VALOR_COMPRA' => 'valor_compra',
            'VALOR_INICIAL' => 'valor_inicial',
            'VALOR_DEPREC' => 'valor_deprec',
            'CODIGO_BARRA' => 'codigo_barra',
        ];

        // Mapeo de estado_conserv numérico a texto
        $estadoConservMap = [
            '1' => 'BUENO',
            '2' => 'REGULAR',
            '3' => 'MALO',
            '4' => 'CHATARRA',
            '5' => 'NUEVO',
        ];

        $batch = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $record = [
                    'archivo_origen' => $fileName,
                    'fecha_importacion' => now(),
                    'importado_por' => auth()->id(),
                    'lote_importacion' => $lote,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                foreach ($columnMap as $csvCol => $dbCol) {
                    if (!isset($headers[$csvCol])) {
                        continue;
                    }

                    $colLetter = $headers[$csvCol];
                    $value = $sheet->getCell("{$colLetter}{$row}")->getValue();
                    $value = is_string($value) ? trim($value) : $value;

                    // Conversiones especiales
                    if (in_array($dbCol, ['fecha_compra', 'fecha_alta'])) {
                        $value = $this->parseImportDate($value);
                    } elseif (in_array($dbCol, ['valor_compra', 'valor_inicial', 'valor_deprec'])) {
                        $value = is_numeric($value) ? $value : null;
                    } elseif ($dbCol === 'estado_conserv') {
                        $value = $estadoConservMap[trim($value)] ?? $value;
                    }

                    $record[$dbCol] = $value ?: null;
                }

                $batch[] = $record;
                $importedCount++;

                // Insertar en lotes de 100
                if (count($batch) >= 100) {
                    PatrimonioAsset::insert($batch);
                    $batch = [];
                }
            } catch (\Exception $e) {
                $errorsCount++;
                if (count($errors) < 10) {
                    $errors[] = "Fila {$row}: " . $e->getMessage();
                }
            }
        }

        // Insertar registros restantes
        if (count($batch) > 0) {
            PatrimonioAsset::insert($batch);
        }

        return response()->json([
            'total' => $totalRows,
            'importados' => $importedCount,
            'errores' => $errorsCount,
            'lote' => $lote,
            'archivo' => $fileName,
            'detalle_errores' => $errors,
        ]);
    }

    public function sincronizarPatrimonio(Request $request)
    {
        $lote = $request->input('lote_importacion');

        $query = PatrimonioAsset::where('sincronizado', false)
            ->whereNotNull('codigo_activo')
            ->where('codigo_activo', '!=', '');

        if ($lote) {
            $query->where('lote_importacion', $lote);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            return response()->json([
                'message' => 'No hay registros pendientes de sincronización',
                'sincronizados' => 0,
                'omitidos' => 0,
                'errores' => 0,
            ]);
        }

        // Pre-cargar caches para evitar N+1
        $statesCache = AssetState::all()->keyBy(fn($s) => mb_strtoupper(trim($s->nombre)));
        $officesCache = HrOffice::activas()->get()->keyBy(fn($o) => mb_strtoupper(trim($o->nombre)));
        $responsiblesCache = AssetResponsible::whereNotNull('nombre_original')
            ->get()
            ->keyBy(fn($r) => mb_strtoupper(trim($r->nombre_original)));

        $sincronizados = 0;
        $omitidos = 0;
        $errors = [];

        foreach ($records as $patrimonioAsset) {
            try {
                $asset = Asset::where('codigo_completo', $patrimonioAsset->codigo_activo)->first();

                if (!$asset) {
                    $omitidos++;
                    continue;
                }

                // Actualizar campos básicos del asset desde SIGA
                $updateData = [];
                if ($patrimonioAsset->denominacion) {
                    $updateData['denominacion'] = $patrimonioAsset->denominacion;
                }
                if ($patrimonioAsset->descripcion) {
                    $updateData['descripcion'] = $patrimonioAsset->descripcion;
                }
                if (!empty($updateData)) {
                    $asset->update($updateData);
                }

                // Resolver estado de conservación
                $estadoKey = mb_strtoupper(trim($patrimonioAsset->estado_conserv ?? ''));
                $estado = $statesCache->get($estadoKey);

                // Resolver oficina por nombre (exacto primero, luego parcial)
                $office = null;
                $oficinaRaw = trim($patrimonioAsset->oficina ?? '');
                if ($oficinaRaw) {
                    $oficinaKey = mb_strtoupper($oficinaRaw);
                    $office = $officesCache->get($oficinaKey);
                    if (!$office) {
                        $office = HrOffice::whereRaw('UPPER(nombre) LIKE ?', ['%' . $oficinaKey . '%'])->first();
                    }
                }

                // Resolver responsable por nombre (exacto primero, luego crear)
                $responsible = null;
                $responsableRaw = trim($patrimonioAsset->responsable_final ?? '');
                if ($responsableRaw) {
                    $responsableKey = mb_strtoupper($responsableRaw);
                    $responsible = $responsablesCache->get($responsableKey);
                    if (!$responsible) {
                        $responsible = AssetResponsible::create([
                            'nombre_original' => strtoupper($responsableRaw),
                            'oficina_id' => $office?->id,
                            'activo' => true,
                        ]);
                        $responsablesCache->put($responsableKey, $responsible);
                    }
                }

                // Crear movimiento de sincronización si hay al menos un dato útil
                if ($estado || $office || $responsible) {
                    AssetMovement::create([
                        'asset_id'        => $asset->id,
                        'tipo'            => 'ASIGNACION',
                        'fecha'           => $patrimonioAsset->fecha_alta?->toDateString() ?? now()->toDateString(),
                        'estado_id'       => $estado?->id,
                        'oficina_id'      => $office?->id,
                        'responsable_id'  => $responsible?->id,
                        'inventariador_id' => auth()->id(),
                        'observaciones'   => 'Sincronizado desde SIGA' . ($patrimonioAsset->archivo_origen ? ' - ' . $patrimonioAsset->archivo_origen : ''),
                    ]);
                }

                // Marcar como sincronizado
                $patrimonioAsset->update([
                    'asset_id'             => $asset->id,
                    'sincronizado'         => true,
                    'fecha_sincronizacion' => now(),
                ]);

                $sincronizados++;
            } catch (\Exception $e) {
                \Log::error('SyncPatrimonio error', [
                    'codigo' => $patrimonioAsset->codigo_activo,
                    'msg'    => $e->getMessage(),
                    'file'   => $e->getFile() . ':' . $e->getLine(),
                ]);
                if (count($errors) < 10) {
                    $errors[] = "Código {$patrimonioAsset->codigo_activo}: " . $e->getMessage();
                }
            }
        }

        return response()->json([
            'sincronizados'    => $sincronizados,
            'omitidos'         => $omitidos,
            'errores'          => count($errors),
            'detalle_errores'  => $errors,
            'message'          => "Sincronización completada: {$sincronizados} bienes actualizados, {$omitidos} omitidos.",
        ]);
    }

    /**
     * Reporte PDF: Bienes Muebles en Uso agrupados por denominación
     */
    public function reportBienesMueblesEnUso()
    {
        $states = AssetState::all()->keyBy('id');

        // Obtener todos los assets con su último movimiento (estado actual)
        $assets = Asset::with('latestMovement.state')->get();

        // Agrupar por denominación y contar por estado
        $grouped = [];
        foreach ($assets as $asset) {
            $denom = $asset->denominacion ?? 'SIN DENOMINACIÓN';
            $estadoNombre = optional($asset->latestMovement?->state)->nombre ?? 'DESCONOCIDO';

            if (!isset($grouped[$denom])) {
                $grouped[$denom] = [
                    'denominacion' => $denom,
                    'bueno' => 0,
                    'regular' => 0,
                    'malo' => 0,
                    'total' => 0,
                ];
            }

            $key = mb_strtolower($estadoNombre);
            if (isset($grouped[$denom][$key])) {
                $grouped[$denom][$key]++;
            }
            $grouped[$denom]['total']++;
        }

        // Ordenar por denominación
        $data = collect($grouped)->sortBy('denominacion')->values();

        // Totales generales
        $totals = [
            'bueno' => $data->sum('bueno'),
            'regular' => $data->sum('regular'),
            'malo' => $data->sum('malo'),
            'total' => $data->sum('total'),
        ];

        $pdf = Pdf::loadView('pdf.bienes_muebles_en_uso', [
            'data' => $data,
            'totals' => $totals,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('reporte_bienes_muebles_en_uso.pdf');
    }

    private function parseImportDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            $parsed = date_create($value);
            return $parsed ? $parsed->format('Y-m-d') : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
