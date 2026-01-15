<?php

namespace App\Http\Controllers;

use App\Models\Occurrence;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OccurrenceController extends Controller
{
    /**
     * Display a listing of occurrences.
     */
    public function index(Request $request)
    {
        $query = Occurrence::with(['vigilante', 'creator'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc');

        // Apply filters if provided
        if ($request->has('tipo') && $request->tipo) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('fecha_desde') && $request->fecha_desde) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta') && $request->fecha_hasta) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                    ->orWhereHas('vigilante', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $occurrences = $query->get()->map(function ($occurrence) {
            return [
                'id' => $occurrence->id,
                'fecha' => $occurrence->fecha->format('Y-m-d'),
                'hora' => $occurrence->hora ? $occurrence->hora->format('H:i') : '',
                'turno' => $occurrence->turno,
                'tipo' => $occurrence->tipo,
                'descripcion' => $occurrence->descripcion,
                'acciones' => $occurrence->acciones,
                'estado' => $occurrence->estado,
                'vigilante' => $occurrence->vigilante?->full_name ?? 'Sin asignar',
                'vigilante_id' => $occurrence->vigilante_id,
            ];
        });

        return Inertia::render('Occurrences/Index', [
            'occurrences' => $occurrences,
            'filters' => $request->only(['tipo', 'fecha_desde', 'fecha_hasta', 'search']),
        ]);
    }

    /**
     * Store a newly created occurrence.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'hora' => 'required',
            'turno' => 'required|in:Mañana,Tarde,Noche',
            'tipo' => 'required|in:Incidente,Emergencia,Rutina,Aviso,Robo,Otros',
            'descripcion' => 'required|string|min:10',
            'acciones' => 'nullable|string',
        ], [
            'fecha.required' => 'La fecha es obligatoria.',
            'hora.required' => 'La hora es obligatoria.',
            'turno.required' => 'El turno es obligatorio.',
            'tipo.required' => 'El tipo es obligatorio.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
        ]);

        $occurrence = Occurrence::create([
            ...$validated,
            'vigilante_id' => auth()->id(),
            'created_by' => auth()->id(),
            'estado' => 'Pendiente',
        ]);

        AuditLog::log(
            auth()->id(),
            'Crear Ocurrencia',
            "Se registró una nueva ocurrencia de tipo {$occurrence->tipo}",
            Occurrence::class,
            $occurrence->id
        );

        return redirect()->route('occurrences.index')
            ->with('success', 'Ocurrencia registrada correctamente.');
    }

    /**
     * Display the specified occurrence.
     */
    public function show(Occurrence $occurrence)
    {
        $occurrence->load(['vigilante', 'creator']);

        return response()->json([
            'id' => $occurrence->id,
            'fecha' => $occurrence->fecha->format('Y-m-d'),
            'hora' => $occurrence->hora ? $occurrence->hora->format('H:i') : '',
            'turno' => $occurrence->turno,
            'tipo' => $occurrence->tipo,
            'descripcion' => $occurrence->descripcion,
            'acciones' => $occurrence->acciones,
            'estado' => $occurrence->estado,
            'vigilante' => $occurrence->vigilante?->full_name,
            'created_by' => $occurrence->creator?->full_name,
            'created_at' => $occurrence->created_at->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Get summary statistics.
     */
    public function summary()
    {
        $today = now()->toDateString();

        return response()->json([
            'today' => Occurrence::whereDate('fecha', $today)->count(),
            'pending' => Occurrence::where('estado', 'Pendiente')->count(),
            'emergencies' => Occurrence::whereDate('fecha', $today)
                ->whereIn('tipo', ['Emergencia', 'Robo'])
                ->count(),
            'by_type' => [
                'emergencia' => Occurrence::whereDate('fecha', $today)->where('tipo', 'Emergencia')->count(),
                'incidente' => Occurrence::whereDate('fecha', $today)->where('tipo', 'Incidente')->count(),
                'rutina' => Occurrence::whereDate('fecha', $today)->where('tipo', 'Rutina')->count(),
                'aviso' => Occurrence::whereDate('fecha', $today)->where('tipo', 'Aviso')->count(),
            ],
        ]);
    }

    /**
     * Generate weekly PDF report.
     */
    public function weeklyReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $occurrences = Occurrence::with('vigilante')
            ->whereDate('fecha', '>=', $startDate)
            ->whereDate('fecha', '<=', $endDate)
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        $stats = [
            'total' => $occurrences->count(),
            'emergencias' => $occurrences->whereIn('tipo', ['Emergencia', 'Robo'])->count(),
            'incidentes' => $occurrences->where('tipo', 'Incidente')->count(),
            'rutina' => $occurrences->where('tipo', 'Rutina')->count(),
            'avisos' => $occurrences->where('tipo', 'Aviso')->count(),
            'otros' => $occurrences->where('tipo', 'Otros')->count(),
        ];

        $pdf = Pdf::loadView('pdf.reporte-ocurrencias', [
            'occurrences' => $occurrences,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stats' => $stats,
            'generatedBy' => auth()->user()?->full_name,
        ]);

        $pdf->setPaper('A4', 'landscape');

        $filename = "SGCI_Reporte_Ocurrencias_{$startDate}_{$endDate}.pdf";

        AuditLog::log(
            auth()->id(),
            'Generar Reporte',
            "Se generó reporte semanal de ocurrencias ({$startDate} al {$endDate})",
            Occurrence::class,
            null
        );

        return $pdf->download($filename);
    }
}
