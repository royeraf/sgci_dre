<?php

namespace App\Http\Controllers;

use App\Models\ExternalVisit;
use App\Models\AuditLog;
use App\Models\Person;
use App\Models\HRArea;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ExternalVisitController extends Controller
{
    /**
     * Display a listing of visitors.
     */
    public function index(Request $request)
    {
        $query = ExternalVisit::with(['registrador', 'person', 'area'])
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
                 });
             });
        }

        $visits = $query->paginate(10)->through(function ($visit) {
            return [
                'id' => $visit->id,
                'fecha' => $visit->fecha->format('Y-m-d'),
                'dni' => $visit->dni, // Accessor
                'nombres' => $visit->nombres, // Accessor (Nombre completo)
                'hora_ingreso' => $visit->hora_ingreso ? $visit->hora_ingreso->format('H:i') : null,
                'hora_salida' => $visit->hora_salida ? $visit->hora_salida->format('H:i') : null,
                'motivo' => $visit->motivo,
                'area' => $visit->area_nombre, // Accessor
                'a_quien_visita' => $visit->a_quien_visita,
                'is_pending' => is_null($visit->hora_salida),
                'registrado_por' => $visit->registrador ? $visit->registrador->name : 'N/A',
            ];
        });

        return Inertia::render('Visitors/Index', [
            'visits' => $visits,
            'filters' => $request->only(['fecha', 'estado', 'search']),
            'areas' => HRArea::where('activo', true)->orderBy('nombre')->get(),
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
            'area_id' => 'required|uuid|exists:hr_areas,id',
            'a_quien_visita' => 'nullable|string|max:200',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'El apellido es obligatorio.',
            'hora_ingreso.required' => 'La hora de ingreso es obligatoria.',
            'motivo.required' => 'El motivo es obligatorio.',
            'area_id.required' => 'El área/oficina es obligatoria.',
            'area_id.exists' => 'El área seleccionada no es válida.',
        ]);

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
        
        // 2. Crear Visita con el area_id directo
        $visit = ExternalVisit::create([
            'person_id' => $person->id,
            'area_id' => $validated['area_id'],
            'hora_ingreso' => $validated['hora_ingreso'],
            'motivo' => $validated['motivo'],
            'a_quien_visita' => $validated['a_quien_visita'],
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
        ])->setPaper([0, 0, 226, 600], 'portrait'); // ~80mm width ticket

        return $pdf->stream("ticket_visita_{$visit->id}.pdf"); // Stream instead of download for better print experience
    }
}
