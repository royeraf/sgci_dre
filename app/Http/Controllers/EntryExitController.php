<?php

namespace App\Http\Controllers;

use App\Models\EntryExit;
use App\Models\Employee;
use App\Models\EntryExitReason;
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
        $query = EntryExit::with(['employee.person', 'employee.contractType', 'registeredBy', 'reason'])
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
                'reason_nombre' => $entry->reason?->nombre,
                'papeleta' => $entry->papeleta,
                'regimen' => $entry->regimen,
                'is_pending' => is_null($entry->hora_retorno),
            ];
        });

        return Inertia::render('EntryExits/Index', [
            'entries' => $entries,
            'filters' => $request->only(['turno', 'fecha', 'estado']),
            'reasons' => EntryExitReason::active()->orderBy('nombre')->get(['id', 'nombre', 'tipo']),
        ]);
    }

    /**
     * Search employees for autocomplete.
     */
    public function searchPersonnel(Request $request)
    {
        $query = $request->input('query');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $employees = Employee::where('estado', 'ACTIVO')
            ->whereHas('person', function ($q) use ($query) {
                $q->where('dni', 'like', "%{$query}%")
                  ->orWhere('nombres', 'like', "%{$query}%")
                  ->orWhere('apellidos', 'like', "%{$query}%");
            })
            ->with(['person', 'position', 'direction', 'contractType'])
            ->limit(15)
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'dni' => $e->dni,
                    'nombre' => $e->full_name,
                    'cargo' => $e->cargo ?? 'N/A',
                    'area' => $e->direction_nombre ?? 'N/A',
                    'regimen' => $e->tipo_contrato ?? 'N/A',
                ];
            });

        return response()->json($employees);
    }

    /**
     * Store a newly created entry/exit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'          => 'required|exists:employees,id',
            'hora_salida'          => 'required',
            'entry_exit_reason_id' => 'required|exists:entry_exit_reasons,id',
            'motivo'               => 'required|string|min:5',
            'turno'                => 'required|in:Mañana,Tarde,Noche',
        ], [
            'employee_id.required'          => 'Debe seleccionar un empleado.',
            'employee_id.exists'            => 'El empleado seleccionado no existe.',
            'hora_salida.required'          => 'La hora de salida es obligatoria.',
            'entry_exit_reason_id.required' => 'Debe seleccionar un motivo.',
            'entry_exit_reason_id.exists'   => 'El motivo seleccionado no es válido.',
            'motivo.required'               => 'La descripción del motivo es obligatoria.',
            'motivo.min'                    => 'El motivo debe tener al menos 5 caracteres.',
            'turno.required'                => 'El turno es obligatorio.',
        ]);

        $entryExit = EntryExit::create([
            ...$validated,
            'fecha' => now()->toDateString(),
            'papeleta' => EntryExit::generatePapeletaNumber(),
            'registrado_por' => auth()->id(),
        ]);

        $entryExit->load('employee.person');

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

        $entryExit->load('employee.person');

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
        $entryExit->load(['employee.person', 'employee.contractType', 'registeredBy']);

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
        $pending = EntryExit::with(['employee.person'])
            ->today()
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
     * Report stats for a date range.
     */
    public function reportStats(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $query = EntryExit::whereBetween('fecha', [$request->start_date, $request->end_date]);

        $total      = (clone $query)->count();
        $retornados = (clone $query)->whereNotNull('hora_retorno')->count();
        $pendientes = (clone $query)->whereNull('hora_retorno')->count();
        $comisiones = (clone $query)->whereHas('reason', fn ($q) => $q->where('tipo', 'comision'))->count();
        $permisos   = (clone $query)->whereHas('reason', fn ($q) => $q->where('tipo', 'permiso'))->count();

        return response()->json([
            'total'      => $total,
            'retornados' => $retornados,
            'pendientes' => $pendientes,
            'comisiones' => $comisiones,
            'permisos'   => $permisos,
        ]);
    }

    /**
     * Generate a list PDF report for a date range.
     */
    public function reportPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $entries = EntryExit::with(['employee.person', 'employee.contractType'])
            ->whereBetween('fecha', [$request->start_date, $request->end_date])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora_salida', 'asc')
            ->get()
            ->map(function ($e) {
                return [
                    'fecha'          => $e->fecha->format('d/m/Y'),
                    'dni'            => $e->dni,
                    'personal'       => $e->nombre_personal,
                    'regimen'        => $e->regimen,
                    'turno'          => $e->turno,
                    'hora_salida'    => $e->hora_salida ? $e->hora_salida->format('H:i') : '--:--',
                    'hora_retorno'   => $e->hora_retorno ? $e->hora_retorno->format('H:i') : '--:--',
                    'tipo_motivo'    => $e->tipo_motivo === 'comision' ? 'Comisión' : 'Permiso',
                    'motivo'         => $e->motivo,
                    'papeleta'       => $e->papeleta,
                ];
            });

        $pdf = Pdf::loadView('pdf.entry_exit_report', [
            'entries'    => $entries,
            'start_date' => \Carbon\Carbon::parse($request->start_date)->format('d/m/Y'),
            'end_date'   => \Carbon\Carbon::parse($request->end_date)->format('d/m/Y'),
            'total'      => $entries->count(),
            'retornados' => $entries->filter(fn($e) => $e['hora_retorno'] !== '--:--')->count(),
            'pendientes' => $entries->filter(fn($e) => $e['hora_retorno'] === '--:--')->count(),
        ])->setPaper('a4', 'landscape');

        $filename = "control_personal_{$request->start_date}_{$request->end_date}.pdf";
        return $pdf->download($filename);
    }

    // ─── Reasons CRUD ────────────────────────────────────────────────────────

    public function getReasons()
    {
        return response()->json(EntryExitReason::orderBy('nombre')->get());
    }

    public function storeReason(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100|unique:entry_exit_reasons,nombre',
            'descripcion' => 'nullable|string|max:500',
            'tipo'        => 'required|in:comision,permiso,ambos',
            'is_active'   => 'boolean',
        ]);

        EntryExitReason::create($validated);
        return response()->json(['message' => 'Motivo creado correctamente.'], 201);
    }

    public function updateReason(Request $request, EntryExitReason $reason)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:100|unique:entry_exit_reasons,nombre,' . $reason->id,
            'descripcion' => 'nullable|string|max:500',
            'tipo'        => 'required|in:comision,permiso,ambos',
            'is_active'   => 'boolean',
        ]);

        $reason->update($validated);
        return response()->json(['message' => 'Motivo actualizado correctamente.']);
    }

    public function deleteReason(EntryExitReason $reason)
    {
        $reason->delete();
        return response()->json(['message' => 'Motivo eliminado correctamente.']);
    }

    // ─────────────────────────────────────────────────────────────────────────

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
