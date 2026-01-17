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

        return Inertia::render('EntryExits/Index', [
            'entries' => $entries,
            'filters' => $request->only(['turno', 'fecha', 'estado']),
        ]);
    }

    /**
     * Search personnel (Staff and Employees) for autocomplete.
     */
    public function searchPersonnel(Request $request)
    {
        $query = $request->input('query');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        // Search in Staff (Vigilantes)
        $staff = Staff::active()
            ->where(function($q) use ($query) {
                $q->where('dni', 'like', "%{$query}%")
                  ->orWhere('nombres', 'like', "%{$query}%")
                  ->orWhere('apellidos', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'dni' => $s->dni,
                    'nombre' => $s->full_name,
                    'cargo' => $s->cargo,
                    'area' => $s->area,
                    'regimen' => $s->regimen,
                    'tipo' => 'vigilante',
                ];
            });

        // Search in Employees (RRHH)
        $employees = \App\Models\Employee::where('estado', 'ACTIVO')
            ->where(function($q) use ($query) {
                $q->where('dni', 'like', "%{$query}%")
                  ->orWhere('nombres', 'like', "%{$query}%")
                  ->orWhere('apellidos', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($e) {
                return [
                    'id' => 'emp-' . $e->id,
                    'dni' => $e->dni,
                    'nombre' => trim($e->nombres . ' ' . $e->apellidos),
                    'cargo' => $e->cargo,
                    'area' => $e->area,
                    'regimen' => $e->tipo_contrato ?? 'N/A',
                    'tipo' => 'empleado',
                ];
            });

        // Combine and limit total results
        $results = $staff->concat($employees)->take(15)->values();

        return response()->json($results);
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
    /**
     * Get absent personnel (Vacations and Licenses).
     */
    public function getAbsentPersonnel()
    {
        $today = now()->toDateString();

        $vacations = \App\Models\Vacation::with('employee')
            ->where('fecha_inicio', '<=', $today)
            ->where('fecha_fin', '>=', $today)
            ->whereIn('estado', ['PROGRAMADO', 'EN CURSO'])
            ->get()
            ->map(function ($v) {
                $employee = $v->employee;
                $nombres = $employee
                    ? trim($employee->nombres . ' ' . $employee->apellidos)
                    : 'Empleado no encontrado';

                return [
                    'id' => 'vac-' . $v->id,
                    'type' => 'Vacaciones',
                    'class' => 'bg-blue-100 text-blue-700',
                    'dni' => $v->dni,
                    'nombres' => $nombres,
                    'desde' => $v->fecha_inicio->format('d/m/Y'),
                    'hasta' => $v->fecha_fin->format('d/m/Y'),
                    'motivo' => $v->observaciones ?? 'Vacaciones programadas',
                    'area' => $employee->area ?? '-',
                ];
            });

        $licenses = \App\Models\License::with('employee')
            ->where('fecha_inicio', '<=', $today)
            ->where('fecha_fin', '>=', $today)
            ->get()
            ->map(function ($l) {
                $employee = $l->employee;
                $nombres = $employee
                    ? trim($employee->nombres . ' ' . $employee->apellidos)
                    : 'Empleado no encontrado';

                return [
                    'id' => 'lic-' . $l->id,
                    'type' => 'Licencia (' . $l->tipo_licencia . ')',
                    'class' => 'bg-purple-100 text-purple-700',
                    'dni' => $l->dni,
                    'nombres' => $nombres,
                    'desde' => $l->fecha_inicio->format('d/m/Y'),
                    'hasta' => $l->fecha_fin->format('d/m/Y'),
                    'motivo' => $l->motivo,
                    'area' => $employee->area ?? '-',
                ];
            });

        return response()->json(array_merge($vacations->toArray(), $licenses->toArray()));
    }
}
