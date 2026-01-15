<?php

namespace App\Http\Controllers;

use App\Models\EntryExit;
use App\Models\Staff;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class EntryExitController extends Controller
{
    /**
     * Display a listing of entry/exits.
     */
    public function index(Request $request)
    {
        $query = EntryExit::with(['staff', 'registeredBy'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_salida', 'desc');

        // Apply filters
        if ($request->has('turno') && $request->turno) {
            $query->where('turno', $request->turno);
        }

        if ($request->has('fecha') && $request->fecha) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->has('estado')) {
            if ($request->estado === 'pendiente') {
                $query->whereNull('hora_retorno');
            } elseif ($request->estado === 'completado') {
                $query->whereNotNull('hora_retorno');
            }
        }

        $entries = $query->get()->map(function ($entry) {
            return [
                'id' => $entry->id,
                'fecha' => $entry->fecha->format('Y-m-d'),
                'dni' => $entry->dni,
                'personal' => $entry->nombre_personal,
                'turno' => $entry->turno,
                'hora_salida' => $entry->hora_salida ? $entry->hora_salida->format('H:i') : null,
                'hora_retorno' => $entry->hora_retorno ? $entry->hora_retorno->format('H:i') : null,
                'motivo' => $entry->motivo,
                'tipo_motivo' => $entry->tipo_motivo,
                'papeleta' => $entry->papeleta,
                'regimen' => $entry->regimen,
                'is_pending' => is_null($entry->hora_retorno),
            ];
        });

        // Get staff list for the form
        $staff = Staff::active()->orderBy('apellidos')->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'dni' => $s->dni,
                'nombre' => $s->full_name,
                'cargo' => $s->cargo,
                'area' => $s->area,
                'regimen' => $s->regimen,
            ];
        });

        return Inertia::render('EntryExits/Index', [
            'entries' => $entries,
            'staff' => $staff,
            'filters' => $request->only(['turno', 'fecha', 'estado']),
        ]);
    }

    /**
     * Store a newly created entry/exit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
            'nombre_personal' => 'required|string|max:200',
            'hora_salida' => 'required',
            'tipo_motivo' => 'required|in:comision,permiso',
            'motivo' => 'required|string|min:5',
            'turno' => 'required|in:Mañana,Tarde,Noche',
            'regimen' => 'nullable|string|max:50',
            'staff_id' => 'nullable|exists:staff,id',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'nombre_personal.required' => 'El nombre del personal es obligatorio.',
            'hora_salida.required' => 'La hora de salida es obligatoria.',
            'tipo_motivo.required' => 'Debe seleccionar el tipo de motivo.',
            'motivo.required' => 'El motivo es obligatorio.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'turno.required' => 'El turno es obligatorio.',
        ]);

        $entryExit = EntryExit::create([
            ...$validated,
            'fecha' => now()->toDateString(),
            'papeleta' => EntryExit::generatePapeletaNumber(),
            'registrado_por' => auth()->id(),
        ]);

        AuditLog::log(
            auth()->id(),
            'Crear Salida',
            "Se registró salida de {$entryExit->nombre_personal} (DNI: {$entryExit->dni})",
            EntryExit::class,
            $entryExit->id
        );

        return redirect()->route('entry-exits.index')
            ->with('success', 'Salida registrada correctamente.');
    }

    /**
     * Update to register return time.
     */
    public function registerReturn(Request $request, EntryExit $entryExit)
    {
        $validated = $request->validate([
            'hora_retorno' => 'required',
        ], [
            'hora_retorno.required' => 'La hora de retorno es obligatoria.',
        ]);

        $entryExit->update([
            'hora_retorno' => $validated['hora_retorno'],
        ]);

        AuditLog::log(
            auth()->id(),
            'Registrar Retorno',
            "Se registró retorno de {$entryExit->nombre_personal} a las {$validated['hora_retorno']}",
            EntryExit::class,
            $entryExit->id
        );

        return redirect()->route('entry-exits.index')
            ->with('success', 'Retorno registrado correctamente.');
    }

    /**
     * Generate PDF for the entry/exit.
     */
    public function generatePdf(EntryExit $entryExit)
    {
        $entryExit->load(['staff', 'registeredBy']);

        $pdf = Pdf::loadView('pdf.papeleta', [
            'entry' => $entryExit,
        ])->setPaper('a5', 'portrait');

        return $pdf->download("papeleta_{$entryExit->papeleta}.pdf");
    }

    /**
     * Get today's pending returns.
     */
    public function pendingReturns()
    {
        $pending = EntryExit::today()
            ->pending()
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'dni' => $entry->dni,
                    'nombre' => $entry->nombre_personal,
                    'hora_salida' => $entry->hora_salida ? $entry->hora_salida->format('H:i') : null,
                    'motivo' => $entry->motivo,
                ];
            });

        return response()->json([
            'count' => $pending->count(),
            'entries' => $pending,
        ]);
    }
}
