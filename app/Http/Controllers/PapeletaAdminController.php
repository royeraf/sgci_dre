<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EntryExitReason;
use App\Models\PapeletaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class PapeletaAdminController extends Controller
{
    /**
     * Get the employee associated with the current user.
     */
    private function getEmployee()
    {
        return Auth::user()->employee;
    }

    /**
     * Build base query filtered by role: ROL011 sees their area, ROL009 sees all.
     */
    private function baseQuery()
    {
        $user = Auth::user();
        $query = PapeletaRequest::with([
            'employee.person', 
            'employee.direction', 
            'employee.office', 
            'employee.position', 
            'reason', 
            'aprobador.person',
            'aprobadorJefe.person',
            'aprobadorRrhh.person'
        ]);

        if ($user->rol_id === 'ROL009' || $user->rol_id === 'ROL001') {
            return $query;
        }

        // ROL011: filter by direction/office
        $employee = $this->getEmployee();
        if (!$employee) {
            return $query->whereRaw('1 = 0'); // empty result
        }

        return $query->where(function ($q) use ($employee) {
            // Employees in same direction
            if ($employee->direction_id) {
                $q->whereHas('employee', function ($sub) use ($employee) {
                    $sub->where('direction_id', $employee->direction_id);
                });
            }
        });
    }

    /**
     * Show papeletas page.
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee?->load('person', 'direction', 'office', 'position');

        return Inertia::render('Papeletas/Index', [
            'userRole'   => $user->rol_id,
            'myEmployee' => $employee,
            'reasons'    => EntryExitReason::active()->get(['id', 'nombre']),
        ]);
    }

    /**
     * Get current user's own papeletas (API).
     */
    public function getMisPapeletas()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([]);
        }

        $papeletas = PapeletaRequest::where('employee_id', $employee->id)
            ->with(['reason', 'aprobadorJefe.person', 'aprobadorRrhh.person'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($papeletas);
    }

    /**
     * Create a papeleta for the current user (self-service).
     */
    public function storeMiPapeleta(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'No tiene un empleado asociado a su cuenta.'], 422);
        }

        $validated = $request->validate([
            'entry_exit_reason_id'    => 'required|exists:entry_exit_reasons,id',
            'motivo'                  => 'required|string|max:500',
            'fecha_salida'            => 'required|date|after_or_equal:today',
            'hora_salida_estimada'    => 'required|date_format:H:i',
            'hora_retorno_estimada'   => 'nullable|date_format:H:i|after:hora_salida_estimada',
            'turno'                   => 'required|in:Manana,Tarde,Noche',
        ], [
            'entry_exit_reason_id.required' => 'Seleccione un motivo de salida.',
            'motivo.required'               => 'La justificación es obligatoria.',
            'fecha_salida.required'         => 'La fecha de salida es obligatoria.',
            'fecha_salida.after_or_equal'   => 'La fecha no puede ser anterior a hoy.',
            'hora_salida_estimada.required' => 'La hora de salida es obligatoria.',
            'hora_retorno_estimada.after'   => 'La hora de retorno debe ser posterior a la salida.',
            'turno.required'                => 'Seleccione un turno.',
        ]);

        $papeleta = PapeletaRequest::create([
            'numero_papeleta'      => PapeletaRequest::generateNumeroPapeleta(),
            'employee_id'          => $employee->id,
            'entry_exit_reason_id' => $validated['entry_exit_reason_id'],
            'motivo'               => $validated['motivo'],
            'fecha_salida'         => $validated['fecha_salida'],
            'hora_salida_estimada' => $validated['hora_salida_estimada'],
            'hora_retorno_estimada'=> $validated['hora_retorno_estimada'] ?? null,
            'turno'                => $validated['turno'],
        ]);

        return response()->json(
            $papeleta->load(['reason', 'aprobadorJefe.person', 'aprobadorRrhh.person']),
            201
        );
    }

    /**
     * Get pending papeletas (API).
     */
    public function getPendientes(Request $request)
    {
        $papeletas = $this->baseQuery()
            ->whereIn('estado', ['PENDIENTE', 'PENDIENTE_RRHH'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($papeletas);
    }

    /**
     * Get historial of papeletas (API).
     */
    public function getHistorial(Request $request)
    {
        $query = $this->baseQuery();

        if ($request->filled('estado') && $request->estado !== 'TODOS') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
        }

        if ($request->filled('direction_id')) {
            $query->forDirection($request->direction_id);
        }

        if ($request->filled('office_id')) {
            $query->forOffice($request->office_id);
        }

        $papeletas = $query->orderBy('created_at', 'desc')->get();

        return response()->json($papeletas);
    }

    /**
     * Approve a papeleta.
     */
    public function aprobar(Request $request, string $papeletaId)
    {
        $papeleta = PapeletaRequest::findOrFail($papeletaId);
        $employee = $this->getEmployee();

        if ($papeleta->estado === 'APROBADO' || $papeleta->estado === 'DESAPROBADO') {
            return response()->json(['message' => 'La papeleta ya fue procesada.'], 422);
        }

        if ($papeleta->estado === 'PENDIENTE') {
            $papeleta->update([
                'estado' => 'PENDIENTE_RRHH',
                'aprobado_por_jefe' => $employee?->id,
                'fecha_aprobacion_jefe' => now(),
                'comentario_aprobacion' => $request->input('comentario', null),
            ]);

            return response()->json([
                'message' => 'Papeleta aprobada por jefe inmediato. Esperando aprobación de RRHH.',
                'papeleta' => $papeleta->fresh(['employee.person', 'reason', 'aprobadorJefe.person']),
            ]);
        }

        if ($papeleta->estado === 'PENDIENTE_RRHH') {
            $papeleta->update([
                'estado' => 'APROBADO',
                'aprobado_por_rrhh' => $employee?->id,
                'fecha_aprobacion_rrhh' => now(),
            ]);

            return response()->json([
                'message' => 'Papeleta aprobada por RRHH.',
                'papeleta' => $papeleta->fresh(['employee.person', 'reason', 'aprobadorJefe.person', 'aprobadorRrhh.person']),
            ]);
        }

        return response()->json(['message' => 'Estado no válido para aprobar.'], 422);
    }

    /**
     * Reject a papeleta.
     */
    public function desaprobar(Request $request, string $papeletaId)
    {
        $request->validate([
            'comentario' => 'required|string|max:500',
        ], [
            'comentario.required' => 'Debe indicar el motivo del rechazo.',
        ]);

        $papeleta = PapeletaRequest::findOrFail($papeletaId);
        $employee = $this->getEmployee();

        if ($papeleta->estado === 'APROBADO' || $papeleta->estado === 'DESAPROBADO') {
            return response()->json(['message' => 'La papeleta ya fue procesada.'], 422);
        }

        if ($papeleta->estado === 'PENDIENTE') {
            $papeleta->update([
                'estado' => 'DESAPROBADO',
                'aprobado_por_jefe' => $employee?->id,
                'fecha_aprobacion_jefe' => now(),
                'comentario_aprobacion' => $request->comentario,
            ]);
        } elseif ($papeleta->estado === 'PENDIENTE_RRHH') {
            $papeleta->update([
                'estado' => 'DESAPROBADO',
                'aprobado_por_rrhh' => $employee?->id,
                'fecha_aprobacion_rrhh' => now(),
                'comentario_aprobacion' => $request->comentario,
            ]);
        }

        return response()->json([
            'message' => 'Papeleta desaprobada.',
            'papeleta' => $papeleta->fresh(['employee.person', 'reason', 'aprobadorJefe.person', 'aprobadorRrhh.person']),
        ]);
    }

    /**
     * Get statistics (API).
     */
    public function getStats()
    {
        $query = $this->baseQuery();

        $total = (clone $query)->count();
        $pendientes = (clone $query)->where('estado', 'PENDIENTE')->count();
        $pendientesRrhh = (clone $query)->where('estado', 'PENDIENTE_RRHH')->count();
        $aprobadas = (clone $query)->aprobado()->count();
        $desaprobadas = (clone $query)->desaprobado()->count();

        return response()->json([
            'total' => $total,
            'pendientes' => $pendientes,
            'pendientes_rrhh' => $pendientesRrhh,
            'aprobadas' => $aprobadas,
            'desaprobadas' => $desaprobadas,
        ]);
    }

    /**
     * Generate PDF for a single papeleta.
     */
    public function generatePdf(string $papeletaId)
    {
        $papeleta = PapeletaRequest::with(['employee.person', 'employee.direction', 'employee.office', 'employee.position', 'reason', 'aprobador.person'])
            ->findOrFail($papeletaId);

        $pdf = Pdf::loadView('pdf.papeleta_request', [
            'papeleta' => $papeleta,
        ])->setPaper('a5', 'portrait');

        return $pdf->stream("papeleta_{$papeleta->numero_papeleta}.pdf");
    }

    /**
     * Generate report PDF.
     */
    public function reportPdf(Request $request)
    {
        $query = $this->baseQuery();

        if ($request->filled('estado') && $request->estado !== 'TODOS') {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
        }

        $papeletas = $query->orderBy('fecha_salida', 'desc')->get();

        $pdf = Pdf::loadView('pdf.papeleta_report', [
            'papeletas' => $papeletas,
            'filtros' => [
                'estado' => $request->input('estado', 'TODOS'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta'),
            ],
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('reporte_papeletas.pdf');
    }
}
