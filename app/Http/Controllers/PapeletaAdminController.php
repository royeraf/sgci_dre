<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EntryExitReason;
use App\Models\DigitalCertificate;
use App\Models\PapeletaRequest;
use App\Services\PapeletaRequestSigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class PapeletaAdminController extends Controller
{
    public function __construct(private readonly PapeletaRequestSigningService $signingService)
    {
    }

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
            ,'signatures'
        ]);

        if ($user->rol_id === 'ROL009' || $user->rol_id === 'ROL001') {
            return $query;
        }

        if ($user->rol_id !== 'ROL011') {
            return $query->whereRaw('1 = 0');
        }

        // ROL011: filter by direction/office
        $employee = $this->getEmployee();
        if (!$employee) {
            return $query->whereRaw('1 = 0'); // empty result
        }

        return $query->whereHas('employee', function ($employeeQuery) use ($employee) {
            $employeeQuery
                ->whereHas('office', fn ($office) => $office->where('jefe_inmediato_id', $employee->id))
                ->orWhere(function ($fallback) use ($employee) {
                    $fallback
                        ->whereDoesntHave('office', fn ($office) => $office->whereNotNull('jefe_inmediato_id'))
                        ->whereHas('direction', fn ($direction) => $direction->where('jefe_inmediato_id', $employee->id));
                });
        });
    }

    /**
     * Show papeletas page.
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee?->load('person', 'direction', 'office', 'position', 'contractType');

        return Inertia::render('Papeletas/Index', [
            'userRole'   => $user->rol_id,
            'myEmployee' => $employee,
            'reasons'    => EntryExitReason::active()->get(['id', 'nombre', 'tipo']),
            'certificate' => $this->certificatePublicData($this->certificateForDni($employee?->dni)),
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
            ->with(['reason', 'aprobadorJefe.person', 'aprobadorRrhh.person', 'signatures'])
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

        if (!$employee->jefe_inmediato) {
            return response()->json(['message' => 'RR. HH. debe asignarle un jefe inmediato antes de solicitar una papeleta.'], 422);
        }

        $validated = $request->validate([
            'destino'                 => 'required|string|max:250',
            'motivo'                  => 'required|string|max:500',
            'motivo_salida'           => 'required|in:comision,particular_compensable,por_salud',
            'signing_pin'             => 'required|string|min:6|max:20',
        ], [
            'destino.required'              => 'Indique el destino.',
            'motivo.required'               => 'La justificación es obligatoria.',
            'motivo_salida.required'        => 'Seleccione el motivo de salida.',
        ]);

        // El formato institucional tiene tres causas fijas. El solicitante no
        // elige ni crea datos personales ni catálogos desde este formulario.
        $reasonData = match ($validated['motivo_salida']) {
            'comision' => ['nombre' => 'Comisión de Servicios', 'tipo' => 'comision'],
            'particular_compensable' => ['nombre' => 'Particular Compensable', 'tipo' => 'permiso'],
            'por_salud' => ['nombre' => 'Por Salud', 'tipo' => 'permiso'],
        };
        $reason = EntryExitReason::firstOrCreate(
            ['nombre' => $reasonData['nombre']],
            ['tipo' => $reasonData['tipo'], 'descripcion' => 'Motivo institucional de papeleta.', 'is_active' => true]
        );
        $reason->update(['tipo' => $reasonData['tipo'], 'is_active' => true]);

        $certificate = $this->requiredCertificate($employee);
        // La fecha, hora y turno se toman del servidor institucional al crear
        // la solicitud. La hora de salida real se obtiene después mediante QR.
        $registeredAt = now();

        $papeleta = PapeletaRequest::create([
            'numero_papeleta'      => PapeletaRequest::generateNumeroPapeleta(),
            'employee_id'          => $employee->id,
            'entry_exit_reason_id' => $reason->id,
            'motivo_salida'        => $validated['motivo_salida'],
            'destino'              => $validated['destino'],
            'motivo'               => $validated['motivo'],
            'fecha_salida'         => $registeredAt->toDateString(),
            'hora_salida_estimada' => $registeredAt->format('H:i'),
            'hora_retorno_estimada'=> null,
            'turno'                => $this->turnoForTime($registeredAt),
            'qr_token'             => (string) Str::uuid(),
            // New records reserve certified AcroForm cells for real QR times.
            // Older signed records remain in their original immutable format.
            'qr_form_enabled'      => true,
        ]);

        try {
            $this->signingService->sign($papeleta, $employee, 'SERVIDOR', $certificate, $validated['signing_pin']);
        } catch (\Throwable $exception) {
            $papeleta->delete();
            throw $exception;
        }

        return response()->json(
            $papeleta->load(['reason', 'aprobadorJefe.person', 'aprobadorRrhh.person', 'signatures']),
            201
        );
    }

    private function turnoForTime(\DateTimeInterface $dateTime): string
    {
        $hour = (int) $dateTime->format('H');

        if ($hour >= 6 && $hour < 14) {
            return 'Manana';
        }

        if ($hour >= 14 && $hour < 22) {
            return 'Tarde';
        }

        return 'Noche';
    }

    /**
     * Get pending papeletas (API).
     */
    public function getPendientes(Request $request)
    {
        $role = Auth::user()->rol_id;
        $states = match ($role) {
            // El jefe inmediato solo procesa solicitudes que aún requieren
            // su firma. Una vez remitidas a RR.HH. ya no deben aparecerle.
            'ROL011' => ['PENDIENTE'],
            // RR.HH. recibe únicamente las solicitudes firmadas por el jefe.
            'ROL009' => ['PENDIENTE_RRHH'],
            // El administrador conserva visibilidad de ambas etapas.
            'ROL001' => ['PENDIENTE', 'PENDIENTE_RRHH'],
            default => [],
        };

        $papeletas = $this->baseQuery()
            ->whereIn('estado', $states)
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
        $validated = $request->validate(['signing_pin' => 'required|string|min:6|max:20']);
        $papeleta = PapeletaRequest::findOrFail($papeletaId);
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json(['message' => 'Su cuenta no tiene un funcionario asociado.'], 422);
        }

        if ($papeleta->estado === 'APROBADO' || $papeleta->estado === 'DESAPROBADO') {
            return response()->json(['message' => 'La papeleta ya fue procesada.'], 422);
        }

        if ($papeleta->estado === 'PENDIENTE') {
            $expectedBoss = $papeleta->employee?->jefe_inmediato;
            if (Auth::user()->rol_id !== 'ROL001' && (!$expectedBoss || $expectedBoss->id !== $employee->id)) {
                return response()->json(['message' => 'Solo el jefe inmediato asignado puede firmar esta papeleta.'], 403);
            }
            $this->signingService->sign($papeleta, $employee, 'JEFE_INMEDIATO', $this->requiredCertificate($employee), $validated['signing_pin']);
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
            if (!in_array(Auth::user()->rol_id, ['ROL009', 'ROL001'], true)) {
                return response()->json(['message' => 'Solo Recursos Humanos puede completar esta firma.'], 403);
            }
            $this->signingService->sign($papeleta, $employee, 'RRHH', $this->requiredCertificate($employee), $validated['signing_pin']);
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
        $role = Auth::user()->rol_id;

        if ($papeleta->estado === 'APROBADO' || $papeleta->estado === 'DESAPROBADO') {
            return response()->json(['message' => 'La papeleta ya fue procesada.'], 422);
        }

        if ($papeleta->estado === 'PENDIENTE') {
            $expectedBoss = $papeleta->employee?->jefe_inmediato;
            if ($role !== 'ROL001' && (!$expectedBoss || $expectedBoss->id !== $employee?->id)) {
                return response()->json(['message' => 'Solo el jefe inmediato asignado puede desaprobar esta papeleta.'], 403);
            }

            $papeleta->update([
                'estado' => 'DESAPROBADO',
                'aprobado_por_jefe' => $employee?->id,
                'fecha_aprobacion_jefe' => now(),
                'comentario_aprobacion' => $request->comentario,
            ]);
        } elseif ($papeleta->estado === 'PENDIENTE_RRHH') {
            if (!in_array($role, ['ROL009', 'ROL001'], true)) {
                return response()->json(['message' => 'Solo Recursos Humanos puede desaprobar esta papeleta en esta etapa.'], 403);
            }

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

        // Los jefes inmediatos no gestionan la segunda etapa ni deben recibir
        // su contador. RR.HH. y el administrador sí la visualizan.
        if (!in_array(Auth::user()->rol_id, ['ROL009', 'ROL001'], true)) {
            $pendientesRrhh = 0;
        }
        if (!in_array(Auth::user()->rol_id, ['ROL011', 'ROL001'], true)) {
            $pendientes = 0;
        }
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
        $papeleta = PapeletaRequest::with(['employee.person', 'employee.direction', 'employee.office', 'employee.position', 'employee.contractType', 'reason', 'aprobador.person', 'signatures'])
            ->findOrFail($papeletaId);

        $currentEmployee = $this->getEmployee();
        $isOwner = $currentEmployee?->id === $papeleta->employee_id;
        $isAssignedBoss = $currentEmployee?->id === $papeleta->employee?->jefe_inmediato?->id;
        $isInstitutionalReviewer = in_array(Auth::user()->rol_id, ['ROL001', 'ROL009'], true);

        if (!$isOwner && !$isAssignedBoss && !$isInstitutionalReviewer) {
            abort(403, 'No tiene permiso para ver esta papeleta.');
        }

        if ($papeleta->executed_document_path && Storage::disk('local')->exists($papeleta->executed_document_path)) {
            return response()->file(Storage::disk('local')->path($papeleta->executed_document_path), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="papeleta_'.$papeleta->numero_papeleta.'_final.pdf"',
            ]);
        }

        $signed = $papeleta->signatures
            ->filter(fn ($signature) => $signature->signed_document_path !== 'pending')
            ->sortByDesc('signed_at')
            ->first();

        // Once any signer has completed a PAdES signature, only ever serve
        // that signed binary. Re-rendering a preview would lose its ByteRange
        // and make a PDF reader correctly report it as unsigned.
        if ($signed && Storage::disk('local')->exists($signed->signed_document_path)) {
            return response()->file(Storage::disk('local')->path($signed->signed_document_path), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="papeleta_'.$papeleta->numero_papeleta.'_firmada.pdf"',
            ]);
        }

        $pdf = Pdf::loadView('pdf.papeleta_request', [
            'papeleta' => $papeleta,
            'preview' => true,
        ])->setPaper('a5', 'portrait');

        return $pdf->stream('papeleta_'.$papeleta->numero_papeleta.'_vista_previa.pdf');
    }

    /** QR para portería. Solo se entrega cuando RR.HH. culminó la aprobación. */
    public function controlQr(string $papeletaId)
    {
        $papeleta = PapeletaRequest::findOrFail($papeletaId);
        abort_unless($papeleta->estado === 'APROBADO' && $papeleta->qr_token, 422, 'El QR se habilita al aprobar la papeleta.');

        $currentEmployee = $this->getEmployee();
        abort_unless(
            $currentEmployee?->id === $papeleta->employee_id || in_array(Auth::user()->rol_id, ['ROL001', 'ROL009', 'ROL011'], true),
            403
        );

        $svg = (new SvgWriter())->write(QrCode::create(route('papeletas.qr.show', $papeleta->qr_token))->setSize(360)->setMargin(12))->getString();
        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'no-store']);
    }

    /** Constancia separada: no altera el PDF PAdES ya firmado. */
    public function controlConstanciaPdf(string $papeletaId)
    {
        $papeleta = PapeletaRequest::with(['employee.person', 'reason'])->findOrFail($papeletaId);
        $employee = $this->getEmployee();
        abort_unless($employee?->id === $papeleta->employee_id || in_array(Auth::user()->rol_id, ['ROL001', 'ROL009', 'ROL011'], true), 403);
        return Pdf::loadView('pdf.papeleta_qr_constancia', compact('papeleta'))->setPaper('a5', 'portrait')
            ->stream('constancia_qr_papeleta_'.$papeleta->numero_papeleta.'.pdf');
    }

    private function certificateForDni(?string $dni): ?DigitalCertificate
    {
        if (!$dni) return null;
        return DigitalCertificate::where('signer_dni', $dni)
            ->where('is_active', true)
            ->where('valid_to', '>', now())
            ->first();
    }

    private function requiredCertificate(Employee $employee): DigitalCertificate
    {
        $certificate = $this->certificateForDni($employee->dni);
        if (!$certificate) {
            throw ValidationException::withMessages(['certificate' => 'Primero debe registrar su certificado RENIEC (.pfx) en su cuenta.']);
        }
        return $certificate;
    }

    private function certificatePublicData(?DigitalCertificate $certificate): ?array
    {
        if (!$certificate) return null;
        return [
            'id' => $certificate->id,
            'subject' => $certificate->certificate_subject,
            'thumbprint' => $certificate->certificate_thumbprint,
            'valid_to' => $certificate->valid_to?->toIso8601String(),
        ];
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
