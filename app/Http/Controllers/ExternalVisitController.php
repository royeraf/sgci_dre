<?php

namespace App\Http\Controllers;

use App\Models\ExternalVisit;
use App\Models\AuditLog;
use App\Services\ReniecService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExternalVisitController extends Controller
{
    /**
     * Display a listing of visitors.
     */
    public function index(Request $request)
    {
        $query = ExternalVisit::with(['registrarUser'])
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
                 $q->where('nombres', 'like', "%{$search}%")
                   ->orWhere('dni', 'like', "%{$search}%")
                   ->orWhere('motivo', 'like', "%{$search}%");
             });
        }

        $visits = $query->paginate(10)->through(function ($visit) {
            return [
                'id' => $visit->id,
                'fecha' => $visit->fecha->format('Y-m-d'),
                'dni' => $visit->dni,
                'nombres' => $visit->nombres,
                'hora_ingreso' => $visit->hora_ingreso ? $visit->hora_ingreso->format('H:i') : null,
                'hora_salida' => $visit->hora_salida ? $visit->hora_salida->format('H:i') : null,
                'motivo' => $visit->motivo,
                'area' => $visit->area,
                'a_quien_visita' => $visit->a_quien_visita,
                'is_pending' => is_null($visit->hora_salida),
                'registrado_por' => $visit->registrarUser ? $visit->registrarUser->name : 'N/A',
            ];
        });

        return Inertia::render('Visitors/Index', [
            'visits' => $visits,
            'filters' => $request->only(['fecha', 'estado', 'search']),
        ]);
    }

    /**
     * Store a newly created visitor entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
            'nombres' => 'required|string|max:200',
            'hora_ingreso' => 'required',
            'motivo' => 'required|string|min:3',
            'area' => 'required|string|max:100',
            'a_quien_visita' => 'nullable|string|max:200',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'nombres.required' => 'El nombre es obligatorio.',
            'hora_ingreso.required' => 'La hora de ingreso es obligatoria.',
            'motivo.required' => 'El motivo es obligatorio.',
            'area.required' => 'El área/oficina es obligatoria.',
        ]);

        $visit = ExternalVisit::create([
            ...$validated,
            'fecha' => now()->toDateString(),
            'registrado_por' => auth()->id(),
        ]);

        // AuditLog logic if exists
        if (class_exists(AuditLog::class)) {
            AuditLog::log(
                auth()->id(),
                'Registrar Visita',
                "Se registró ingreso de visitante {$visit->nombres} (DNI: {$visit->dni})",
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

        $resultado = $reniecService->consultarDni($request->dni);

        return response()->json($resultado);
    }
}
