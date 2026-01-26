<?php

namespace App\Http\Controllers;

use App\Models\ExternalVisit;
use App\Models\AuditLog;
use App\Services\ReniecService;
use App\Models\Person;
use App\Models\HRArea;
use App\Models\HrOffice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use App\Models\Employee;

class ExternalVisitController extends Controller
{
    /**
     * Display a listing of visitors.
     */
    public function index(Request $request)
    {
        $query = ExternalVisit::with(['registrador', 'person', 'area', 'office'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_ingreso', 'desc');

        // Apply filters
        if ($request->has('fecha') && $request->fecha) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->has('estado')) {
            if ($request->estado === 'pendiente') {
                $query->whereNull('hora_salida');
            } elseif ($request->estado === 'completado') {
                $query->whereNotNull('hora_salida');
            }
        }
        
        if ($request->has('search') && $request->search) {
             $search = $request->search;
             $query->where(function($q) use ($search) {
                 // Buscar en tabla relacionada people
                 $q->whereHas('person', function($qp) use ($search) {
                     $qp->where('nombres', 'like', "%{$search}%")
                       ->orWhere('apellidos', 'like', "%{$search}%")
                       ->orWhere('dni', 'like', "%{$search}%");
                 })
                 ->orWhere('motivo', 'like', "%{$search}%")
                 // Buscar también por nombre de área relacionada
                 ->orWhereHas('area', function($qa) use ($search) {
                     $qa->where('nombre', 'like', "%{$search}%");
                 })
                 ->orWhereHas('office', function($qo) use ($search) {
                     $qo->where('nombre', 'like', "%{$search}%");
                 });
             });
        }

        $perPage = $request->input('per_page', 10);
        $visits = $query->paginate($perPage)->through(function ($visit) {
            $destino = $visit->office_nombre 
                ? ($visit->office_nombre . ($visit->area_nombre ? " - {$visit->area_nombre}" : '')) 
                : $visit->area_nombre;

            return [
                'id' => $visit->id,
                'fecha' => $visit->fecha->format('Y-m-d'),
                'dni' => $visit->dni, // Accessor
                'nombres' => $visit->nombres, // Accessor (Nombre completo)
                'hora_ingreso' => $visit->hora_ingreso ? $visit->hora_ingreso->format('H:i') : null,
                'hora_salida' => $visit->hora_salida ? $visit->hora_salida->format('H:i') : null,
                'motivo' => $visit->motivo,
                'area' => $destino, // Muestra Oficina - Área o solo Área
                'a_quien_visita' => $visit->a_quien_visita,
                'is_pending' => is_null($visit->hora_salida),
                'registrado_por' => $visit->registrador ? $visit->registrador->name : 'N/A',
            ];
        });

        return Inertia::render('Visitors/Index', [
            'visits' => $visits,
            'filters' => $request->only(['fecha', 'estado', 'search']),
            'areas' => HRArea::where('activo', true)->orderBy('nombre')->get(),
            'areas' => HRArea::where('activo', true)->orderBy('nombre')->get(),
            'offices' => HrOffice::where('activo', true)->with('area')->orderBy('nombre')->get(),
            'employees' => Employee::with('person')->get()->map(function($emp) {
                return [
                    'id' => $emp->id,
                    'nombre_completo' => $emp->person ? $emp->person->nombre_full : 'Sin Datos',
                    'dni' => $emp->dni
                ];
            }),
        ]);
    }

    /**
     * Store a newly created visitor entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'hora_ingreso' => 'required',
            'motivo' => 'required|string|min:3',
            'area_id' => 'nullable|uuid|exists:hr_areas,id',
            'office_id' => 'nullable|uuid|exists:hr_offices,id',
            'a_quien_visita' => 'nullable|string|max:200',
            'employee_id' => 'nullable|uuid|exists:employees,id',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'El apellido es obligatorio.',
            'hora_ingreso.required' => 'La hora de ingreso es obligatoria.',
            'motivo.required' => 'El motivo es obligatorio.',
        ]);

        if (empty($validated['area_id']) && empty($validated['office_id'])) {
             return back()->withErrors(['area_id' => 'Debe seleccionar un Área o una Oficina de destino.']);
        }

        // 1. Buscar o Crear Persona
        $person = Person::firstOrNew(['dni' => $validated['dni']]);
        
        // Solo actualizar datos personales si NO es empleado interno (para no sobrescribir datos oficiales)
        if ($person->tipo !== 'INTERNO') {
            $person->nombres = Str::upper($validated['nombres']);
            $person->apellidos = Str::upper($validated['apellidos']);
            $person->tipo = 'EXTERNO';
            $person->is_active = true;
            $person->save();
        }
        
        // 2. Crear Visita
        // Si elige office pero no area, intentar obtener area (opcional)
        $areaId = $validated['area_id'];
        if (!$areaId && !empty($validated['office_id'])) {
            $office = HrOffice::find($validated['office_id']);
            $areaId = $office ? $office->area_id : null;
        }

        $visit = ExternalVisit::create([
            'person_id' => $person->id,
            'area_id' => $areaId,
            'office_id' => $validated['office_id'] ?? null,
            'hora_ingreso' => $validated['hora_ingreso'],
            'motivo' => $validated['motivo'],
            'motivo' => $validated['motivo'],
            'a_quien_visita' => $validated['a_quien_visita'],
            'employee_id' => $validated['employee_id'] ?? null,
            'fecha' => now()->toDateString(),
            'registrado_por' => auth()->id(),
        ]);

        // AuditLog logic if exists
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                auth()->id(),
                'Registrar Visita',
                "Se registró ingreso de visitante {$person->nombres} (DNI: {$person->dni})",
                ExternalVisit::class,
                $visit->id
            );
        }

        return redirect()->route('visitors.index')
            ->with('success', 'Visita registrada correctamente.')
            ->with('new_visit_id', $visit->id);
    }

    /**
     * Register visitor exit.
     */
    public function registerExit(Request $request, ExternalVisit $visit)
    {
        $validated = $request->validate([
            'hora_salida' => 'required',
        ], [
            'hora_salida.required' => 'La hora de salida es obligatoria.',
        ]);

        $visit->update([
            'hora_salida' => $validated['hora_salida'],
        ]);

        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                auth()->id(),
                'Registrar Salida Visita',
                "Se registró salida de visitante {$visit->nombres} a las {$validated['hora_salida']}",
                ExternalVisit::class,
                $visit->id
            );
        }

        return redirect()->route('visitors.index')
            ->with('success', 'Salida de visita registrada correctamente.');
    }
    /**
     * Generate PDF ticket for the visit.
     */
    public function generateTicket(ExternalVisit $visit)
    {
        // 80mm width, auto height (approximated for A7/Ticket roll)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.pase_visita', [
            'visit' => $visit,
        ])->setPaper([0, 0, 226, 350], 'portrait'); // ~80mm width, reduced height ticket

        return $pdf->stream("ticket_visita_{$visit->id}.pdf"); // Stream instead of download for better print experience
    }

    /**
     * Consultar datos de persona por DNI usando la API de RENIEC.
     * Este endpoint es usado por el lector de códigos de barras.
     */
    public function consultarDni(Request $request, ReniecService $reniecService)
    {
        $request->validate([
            'dni' => 'required|string|size:8',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
        ]);

        // 1. Buscar primero en la base de datos local
        $localPerson = Person::findByDni($request->dni);

        if ($localPerson) {
            return response()->json([
                'success' => true,
                'message' => 'Datos encontrados en registro local',
                'data' => [
                    'dni' => $localPerson->dni,
                    'nombres' => $localPerson->nombres,
                    'apellido_paterno' => $localPerson->apellidos, // Mapeamos todo a paterno para que el frontend lo concatene bien
                    'apellido_materno' => '',
                    'nombre_completo' => $localPerson->nombre_full,
                ]
            ]);
        }

        // 2. Si no existe, consultar a la API de RENIEC
        $resultado = $reniecService->consultarDni($request->dni);

        return response()->json($resultado);
    }
    public function reportStats(Request $request) {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        if (!$start || !$end) return response()->json(['error' => 'Fechas requeridas'], 400);

        $visits = ExternalVisit::with(['area', 'office'])
            ->whereBetween('fecha', [$start, $end])
            ->get();

        $areas = $visits->groupBy(function($v) {
            return $v->area ? $v->area->nombre : ($v->office ? $v->office->nombre : 'Sin Destino');
        })->count();

        return response()->json([
            'total' => $visits->count(),
            'pending' => $visits->whereNull('hora_salida')->count(),
            'completed' => $visits->whereNotNull('hora_salida')->count(),
            'areasCount' => $areas
        ]);
    }

    public function generateReportPdf(Request $request) {
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $visits = ExternalVisit::with(['person', 'area', 'office'])
            ->whereBetween('fecha', [$start, $end])
            ->orderBy('fecha')
            ->orderBy('hora_ingreso')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reporte_visitas', [
            'visits' => $visits,
            'start' => $start,
            'end' => $end
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("reporte_visitas_{$start}_{$end}.pdf");
    }

    /**
     * Buscar visita pendiente por DNI (para escáner de códigos de barras).
     */
    public function findPendingByDni(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
        ]);

        // Buscar visita pendiente del día de hoy con ese DNI
        $visit = ExternalVisit::with(['person', 'area', 'office'])
            ->whereHas('person', function($q) use ($validated) {
                $q->where('dni', $validated['dni']);
            })
            ->whereNull('hora_salida')
            ->whereDate('fecha', now()->toDateString())
            ->orderBy('hora_ingreso', 'desc')
            ->first();

        if (!$visit) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró visita pendiente para el DNI ingresado el día de hoy.'
            ], 404);
        }

        $destino = $visit->office_nombre
            ? ($visit->office_nombre . ($visit->area_nombre ? " - {$visit->area_nombre}" : ''))
            : $visit->area_nombre;

        return response()->json([
            'success' => true,
            'visit' => [
                'id' => $visit->id,
                'fecha' => $visit->fecha->format('Y-m-d'),
                'dni' => $visit->dni,
                'nombres' => $visit->nombres,
                'hora_ingreso' => $visit->hora_ingreso ? $visit->hora_ingreso->format('H:i') : null,
                'hora_salida' => $visit->hora_salida ? $visit->hora_salida->format('H:i') : null,
                'motivo' => $visit->motivo,
                'area' => $destino,
                'a_quien_visita' => $visit->a_quien_visita,
                'is_pending' => is_null($visit->hora_salida),
                'registrado_por' => $visit->registrador ? $visit->registrador->name : 'N/A',
            ]
        ]);
    }
}
