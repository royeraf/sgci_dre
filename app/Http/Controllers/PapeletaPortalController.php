<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\EntryExitReason;
use App\Models\PapeletaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class PapeletaPortalController extends Controller
{
    /**
     * Show portal login page.
     */
    public function showLogin()
    {
        return Inertia::render('Portal/Papeletas/Login');
    }

    /**
     * Handle portal login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'dni' => ['required', 'string', 'size:8', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string'],
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.regex' => 'El DNI solo debe contener números.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = User::where('dni', $request->dni)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => 'Las credenciales proporcionadas son incorrectas.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'credentials' => 'Su cuenta está desactivada. Contacte al administrador.',
            ]);
        }

        if (!in_array($user->rol_id, ['ROL011', 'ROL012'])) {
            throw ValidationException::withMessages([
                'credentials' => 'Su cuenta no tiene acceso al portal de empleados.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $user->update(['ultimo_acceso' => now()]);
        $request->session()->regenerate();

        return redirect('/portal/papeletas');
    }

    /**
     * Handle portal logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/portal/login');
    }

    /**
     * Show papeletas index (dashboard).
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            Auth::logout();
            $request = request();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/portal/login')->with('error', 'No se encontró un empleado asociado a su cuenta. Contacte al administrador.');
        }

        $papeletas = PapeletaRequest::where('employee_id', $employee->id)
            ->with(['reason', 'aprobador.person', 'aprobadorJefe.person', 'aprobadorRrhh.person'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $papeletas->count(),
            'pendientes' => $papeletas->where('estado', 'PENDIENTE')->count(),
            'pendientes_rrhh' => $papeletas->where('estado', 'PENDIENTE_RRHH')->count(),
            'aprobadas' => $papeletas->where('estado', 'APROBADO')->count(),
            'desaprobadas' => $papeletas->where('estado', 'DESAPROBADO')->count(),
        ];

        $reasons = EntryExitReason::active()->get();

        return Inertia::render('Portal/Papeletas/Index', [
            'papeletas' => $papeletas,
            'stats' => $stats,
            'employee' => $employee->load('person', 'direction', 'office', 'position'),
            'reasons' => $reasons,
        ]);
    }

    /**
     * Show create papeleta form.
     */
    public function create()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect('/portal/papeletas')->with('error', 'No se encontró empleado asociado.');
        }

        $reasons = EntryExitReason::active()->get();

        return Inertia::render('Portal/Papeletas/Create', [
            'employee' => $employee->load('person', 'direction', 'office', 'position'),
            'reasons' => $reasons,
        ]);
    }

    /**
     * Store a new papeleta request.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        if (!$employee) {
            return back()->with('error', 'No se encontró empleado asociado.');
        }

        $validated = $request->validate([
            'entry_exit_reason_id' => 'required|exists:entry_exit_reasons,id',
            'motivo' => 'required|string|max:500',
            'fecha_salida' => 'required|date|after_or_equal:today',
            'hora_salida_estimada' => 'required|date_format:H:i',
            'hora_retorno_estimada' => 'nullable|date_format:H:i|after:hora_salida_estimada',
            'turno' => 'required|in:Manana,Tarde,Noche',
        ], [
            'entry_exit_reason_id.required' => 'Seleccione un motivo de salida.',
            'motivo.required' => 'La justificación es obligatoria.',
            'fecha_salida.required' => 'La fecha de salida es obligatoria.',
            'fecha_salida.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hora_salida_estimada.required' => 'La hora de salida es obligatoria.',
            'hora_retorno_estimada.after' => 'La hora de retorno debe ser posterior a la salida.',
            'turno.required' => 'Seleccione un turno.',
        ]);

        $papeleta = PapeletaRequest::create([
            'numero_papeleta' => PapeletaRequest::generateNumeroPapeleta(),
            'employee_id' => $employee->id,
            'entry_exit_reason_id' => $validated['entry_exit_reason_id'],
            'motivo' => $validated['motivo'],
            'fecha_salida' => $validated['fecha_salida'],
            'hora_salida_estimada' => $validated['hora_salida_estimada'],
            'hora_retorno_estimada' => $validated['hora_retorno_estimada'] ?? null,
            'turno' => $validated['turno'],
        ]);

        return redirect('/portal/papeletas')->with('success', 'Papeleta #' . $papeleta->numero_papeleta . ' creada exitosamente.');
    }

    /**
     * Show papeleta detail.
     */
    public function show(string $papeletaId)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $papeleta = PapeletaRequest::where('id', $papeletaId)
            ->where('employee_id', $employee->id)
            ->with(['employee.person', 'employee.direction', 'employee.office', 'employee.position', 'reason', 'aprobador.person'])
            ->firstOrFail();

        return Inertia::render('Portal/Papeletas/Show', [
            'papeleta' => $papeleta,
            'employee' => $employee->load('person', 'direction', 'office', 'position'),
        ]);
    }

    /**
     * Generate PDF for a papeleta.
     */
    public function getPapeletaPdf(string $papeletaId)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $papeleta = PapeletaRequest::where('id', $papeletaId)
            ->where('employee_id', $employee->id)
            ->with(['employee.person', 'employee.direction', 'employee.office', 'employee.position', 'reason', 'aprobador.person'])
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.papeleta_request', [
            'papeleta' => $papeleta,
        ])->setPaper('a5', 'portrait');

        return $pdf->stream("papeleta_{$papeleta->numero_papeleta}.pdf");
    }
}
