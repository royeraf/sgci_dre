<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDigitalCertificate;
use App\Models\Employee;
use App\Models\EntryExitReason;
use App\Models\DigitalCertificate;
use App\Models\PapeletaRequest;
use App\Services\PapeletaRequestSigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class PapeletaAdminController extends Controller
{
    use ResolvesDigitalCertificate;

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
    /**
     * Relaciones a precargar en cualquier listado de papeletas.
     */
    private function papeletaEagerLoads(): array
    {
        return [
            'employee.person',
            'employee.direction',
            'employee.office',
            'employee.position',
            'reason',
            'aprobador.person',
            'aprobadorJefe.person',
            'aprobadorRrhh.person',
            'jefeAsignado.person',
            'signatures',
        ];
    }

    private function baseQuery()
    {
        $user = Auth::user();
        $query = PapeletaRequest::with($this->papeletaEagerLoads());

        if ($user->rol_id === 'ROL009' || $user->rol_id === 'ROL001') {
            return $query;
        }

        // Un empleado no designado en Direcciones/Oficinas puede igual
        // tener papeletas dirigidas a él a mano; solo se descarta si no
        // tiene ningún empleado vinculado.
        $employee = $this->getEmployee();
        if (!$employee) {
            return $query->whereRaw('1 = 0'); // empty result
        }

        return $query->paraBandejaJefe($employee);
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
            'certificate' => $this->certificatePublicData(DigitalCertificate::activeForDni($employee?->dni)),
            // Acceso a la bandeja de jefe: está designado en Direcciones/
            // Oficinas O fue elegido a mano en alguna papeleta puntual.
            'puedeAprobarComoJefe' => $employee?->participaEnEtapaJefe() ?? false,
            // Sugerencia precargada en el buscador de "Nueva Papeleta":
            // titular de su unidad, o si no hay, el primer suplente vigente.
            'jefeSugeridoId' => $employee?->aprobadores_papeleta->first()?->id,
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
            ->with(['reason', 'aprobadorJefe.person', 'aprobadorRrhh.person', 'jefeAsignado.person', 'signatures'])
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
            'destino'                 => 'required|string|max:250',
            'motivo'                  => 'nullable|string|max:500',
            'motivo_salida'           => 'required|in:comision,particular_compensable,por_salud',
            'jefe_asignado_id'        => ['required', 'uuid', Rule::exists('employees', 'id')->where('estado', 'ACTIVO')],
            'signing_pin'             => 'required|string|min:6|max:20',
        ], [
            'destino.required'              => 'Indique el destino.',
            'motivo_salida.required'        => 'Seleccione el motivo de salida.',
            'jefe_asignado_id.required'     => 'Seleccione al jefe que aprobará esta papeleta.',
            'jefe_asignado_id.exists'       => 'El jefe seleccionado no está activo.',
            'signing_pin.required'          => 'Ingrese su clave de firma.',
            'signing_pin.min'               => 'La clave de firma debe tener al menos 6 caracteres.',
        ]);

        if ($validated['jefe_asignado_id'] === $employee->id) {
            throw ValidationException::withMessages([
                'jefe_asignado_id' => 'No puede designarse a usted mismo como jefe que aprueba su papeleta.',
            ]);
        }

        $jefeAsignado = Employee::activos()->with('person')->find($validated['jefe_asignado_id']);
        if (!$jefeAsignado) {
            throw ValidationException::withMessages(['jefe_asignado_id' => 'El jefe seleccionado no está activo.']);
        }

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
            'jefe_asignado_id'     => $jefeAsignado->id,
            'jefe_asignado_dni'    => $jefeAsignado->dni,
            'jefe_asignado_nombre' => $jefeAsignado->full_name,
            'entry_exit_reason_id' => $reason->id,
            'motivo_salida'        => $validated['motivo_salida'],
            'destino'              => $validated['destino'],
            // Columna NOT NULL en BD; "opcional" en el formulario significa
            // que puede quedar vacía, no ausente.
            'motivo'               => $validated['motivo'] ?? '',
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
            $papeleta->load(['reason', 'aprobadorJefe.person', 'aprobadorRrhh.person', 'jefeAsignado.person', 'signatures']),
            201
        );
    }

    /**
     * Lista liviana de empleados activos para el buscador de "quién debe
     * firmar como jefe inmediato" al crear una papeleta. Universo general
     * (no restringido a titulares/suplentes designados).
     */
    public function getPosiblesJefes()
    {
        $employee = $this->getEmployee();
        if (!$employee) {
            return response()->json([]);
        }

        $dnisConCertificado = DigitalCertificate::where('is_active', true)
            ->where('valid_to', '>', now())
            ->pluck('signer_dni')
            ->unique();

        $empleados = Employee::activos()
            ->whereHas('person')
            ->with('person:id,dni,nombres,apellidos')
            ->where('id', '!=', $employee->id)
            ->get()
            ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (Employee $emp) => [
                'id' => $emp->id,
                'dni' => $emp->dni,
                'nombres' => $emp->nombres,
                'apellidos' => $emp->apellidos,
                'tiene_certificado' => $emp->dni && $dnisConCertificado->contains($emp->dni),
            ]);

        return response()->json($empleados);
    }

    private function mensajeNoAutorizadoJefe(PapeletaRequest $papeleta): string
    {
        if ($papeleta->jefe_asignado_nombre) {
            return "Esta papeleta fue dirigida a {$papeleta->jefe_asignado_nombre}. Solo esa persona puede firmarla.";
        }
        return 'Solo el jefe inmediato o un suplente autorizado puede firmar esta papeleta.';
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
        $employee = $this->getEmployee();
        $isHr = in_array($role, ['ROL009', 'ROL001'], true);

        // Etapa jefe: quien está dirigido a mano en alguna papeleta, o
        // designado en Direcciones/Oficinas (papeletas legadas). Se arma
        // con una query PROPIA (no baseQuery()) para que RR.HH. sin
        // designación propia siga sin ver la etapa del jefe de otros —
        // baseQuery() devuelve todo sin scope para ROL009/ROL001.
        $etapaJefe = collect();
        if ($role === 'ROL001') {
            $etapaJefe = $this->baseQuery()->where('estado', 'PENDIENTE')->get();
        } elseif ($employee && $employee->participaEnEtapaJefe()) {
            $etapaJefe = PapeletaRequest::with($this->papeletaEagerLoads())
                ->paraBandejaJefe($employee)
                ->where('estado', 'PENDIENTE')
                ->get();
        }

        // Etapa RR.HH.: sin cambios respecto a hoy.
        $etapaRrhh = $isHr
            ? $this->baseQuery()->where('estado', 'PENDIENTE_RRHH')->get()
            : collect();

        $papeletas = $etapaJefe->concat($etapaRrhh)->sortByDesc('created_at')->values();

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
        $validated = $request->validate([
            'signing_pin' => 'required|string|min:6|max:20',
        ], [
            'signing_pin.required' => 'Ingrese su clave de firma.',
            'signing_pin.min'      => 'La clave de firma debe tener al menos 6 caracteres.',
            'signing_pin.max'      => 'La clave de firma no debe superar los 20 caracteres.',
        ]);
        $papeleta = PapeletaRequest::findOrFail($papeletaId);
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json(['message' => 'Su cuenta no tiene un funcionario asociado.'], 422);
        }

        if ($papeleta->estado === 'APROBADO' || $papeleta->estado === 'DESAPROBADO') {
            return response()->json(['message' => 'La papeleta ya fue procesada.'], 422);
        }

        if ($papeleta->estado === 'PENDIENTE') {
            $rol = Auth::user()->rol_id;
            $autorizado = $rol === 'ROL001' || $papeleta->puedeSerAprobadaPor($employee);
            if (!$autorizado) {
                return response()->json(['message' => $this->mensajeNoAutorizadoJefe($papeleta)], 403);
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
            $autorizado = $role === 'ROL001' || $papeleta->puedeSerAprobadaPor($employee);
            if (!$autorizado) {
                return response()->json(['message' => $this->mensajeNoAutorizadoJefe($papeleta)], 403);
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
        } else {
            return response()->json(['message' => 'Estado no válido para desaprobar.'], 422);
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
        $pendientesRrhh = (clone $query)->where('estado', 'PENDIENTE_RRHH')->count();

        $rol = Auth::user()->rol_id;
        // Los jefes inmediatos no gestionan la segunda etapa ni deben recibir
        // su contador. RR.HH. y el administrador sí la visualizan.
        if (!in_array($rol, ['ROL009', 'ROL001'], true)) {
            $pendientesRrhh = 0;
        }

        // El contador de la etapa del jefe usa la misma regla estricta que
        // getPendientes(): para que el badge de la pestaña nunca desincronice
        // del listado, no se cuenta sobre baseQuery() (que para RR.HH.
        // devuelve todo sin scope).
        $employeeActual = $this->getEmployee();
        if ($rol === 'ROL001') {
            $pendientes = (clone $query)->where('estado', 'PENDIENTE')->count();
        } elseif ($employeeActual && $employeeActual->participaEnEtapaJefe()) {
            $pendientes = PapeletaRequest::paraBandejaJefe($employeeActual)->where('estado', 'PENDIENTE')->count();
        } else {
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
        // Puede ser el jefe esperado (elegido o, en filas legadas, por
        // designación), o quien ya firmó realmente la etapa jefe — este
        // último conserva acceso aunque la designación cambie después.
        $isAssignedBoss = $papeleta->puedeSerAprobadaPor($currentEmployee)
            || ($currentEmployee && $papeleta->aprobado_por_jefe === $currentEmployee->id);
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
