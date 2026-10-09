<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Person;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Services\Planilla\BoletaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Portal público de consulta de boletas: el trabajador se identifica solo con
 * su DNI (no hay cuenta ni sesión de usuario) y puede ver todas sus boletas
 * de los periodos aprobados, descargar el PDF y confirmar que las revisó.
 *
 * Reglas de seguridad:
 *  - El DNI fija la identidad en la sesión; ver/PDF/revisar solo responden si
 *    la boleta pertenece a esa identidad (nunca se sirve un UUID ajeno).
 *  - Nunca se responde 401: el interceptor de axios redirigiría a /login.
 *  - Mensajes uniformes para DNI inexistente/inactivo/sin boletas, de modo que
 *    la respuesta no revele a quién pertenece un DNI.
 *  - La consulta se limita con throttle en la ruta (anti-enumeración).
 */
class BoletaPortalController extends Controller
{
    private const SESSION_KEY = 'boleta_portal_employee_id';

    public function __construct(private BoletaService $boletas)
    {
    }

    /**
     * Página del portal. Si ya se identificó, se le devuelve su lista para que
     * un refresco de la página no lo expulse.
     */
    public function index(Request $request)
    {
        $empleado = $this->identificado($request);
        $boletas = $empleado ? $this->boletas->listarPorEmpleado($empleado) : [];

        if ($empleado && !$boletas) {
            // Perdió la visibilidad (p. ej. el periodo volvió a borrador).
            $this->olvidar($request);
            $empleado = null;
        }

        return Inertia::render('Boletas/Portal', [
            'empleado' => $empleado ? $this->presentarEmpleado($empleado) : null,
            'boletas' => $boletas,
        ]);
    }

    /**
     * Identifica al trabajador por DNI y devuelve todas sus boletas.
     */
    public function consultar(Request $request)
    {
        $validated = $request->validate([
            'dni' => ['required', 'digits:8'],
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.digits' => 'El DNI debe tener exactamente 8 dígitos.',
        ]);

        $empleado = $this->resolverEmpleado($validated['dni']);
        $boletas = $empleado ? $this->boletas->listarPorEmpleado($empleado) : [];

        if (!$boletas) {
            return response()->json([
                'message' => 'No se encontraron boletas para el DNI indicado. Verifique el número o intente más tarde.',
            ], 404);
        }

        $request->session()->put(self::SESSION_KEY, $empleado->id);

        return response()->json([
            'empleado' => $this->presentarEmpleado($empleado),
            'boletas' => $boletas,
        ]);
    }

    /**
     * Cierra la identificación del portal (computador compartido).
     */
    public function salir(Request $request)
    {
        $this->olvidar($request);

        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * Vista previa de una boleta propia.
     */
    public function show(Request $request, PlanillaDetalle $detalle)
    {
        if (!$this->detallePropio($request, $detalle)) {
            return response()->json(['message' => 'Boleta no disponible. Consulte nuevamente con su DNI.'], 404);
        }

        return response()->json($this->boletas->armar($detalle));
    }

    /**
     * PDF de una boleta propia (mismo documento que el módulo admin).
     */
    public function pdf(Request $request, PlanillaDetalle $detalle)
    {
        if (!$this->detallePropio($request, $detalle)) {
            return redirect()->route('boletas.portal');
        }

        $boleta = $this->boletas->armar($detalle);

        return $this->boletas->render($boleta)
            ->stream('boleta_' . $boleta['trabajador']['codigo_boleta'] . '.pdf');
    }

    /**
     * Confirma que el trabajador revisó su boleta. Idempotente: conserva la
     * primera fecha de confirmación.
     */
    public function revisar(Request $request, PlanillaDetalle $detalle)
    {
        if (!$this->detallePropio($request, $detalle)) {
            return response()->json(['message' => 'Boleta no disponible. Consulte nuevamente con su DNI.'], 404);
        }

        if (!$detalle->revisada_en) {
            $detalle->update(['revisada_en' => now()]);
        }

        return response()->json([
            'message' => 'Boleta confirmada como revisada',
            'revisada_en' => $detalle->revisada_en->toDateTimeString(),
        ]);
    }

    /**
     * El detalle existe, pertenece a la identidad de sesión y su periodo es
     * visible fuera del módulo. Si no, el caller responde 404/redirect.
     */
    private function detallePropio(Request $request, PlanillaDetalle $detalle): bool
    {
        $empleado = $this->identificado($request);

        if (!$empleado || $detalle->employee_id !== $empleado->id) {
            return false;
        }

        $periodo = $detalle->periodo;

        return $periodo !== null
            && in_array($periodo->estado, PlanillaPeriodo::ESTADOS_BOLETA_VISIBLE, true);
    }

    /**
     * Empleado ACTIVO cuyo DNI coincide; null si no existe o está inactivo.
     */
    private function resolverEmpleado(string $dni): ?Employee
    {
        $person = Person::findByDni($dni);

        if (!$person) {
            return null;
        }

        return Employee::query()
            ->where('person_id', $person->id)
            ->where('estado', 'ACTIVO')
            ->first();
    }

    /**
     * Identidad vigente en la sesión; limpia la clave si el empleado ya no
     * está activo.
     */
    private function identificado(Request $request): ?Employee
    {
        $empleadoId = $request->session()->get(self::SESSION_KEY);

        if (!$empleadoId) {
            return null;
        }

        $empleado = Employee::find($empleadoId);

        if (!$empleado || $empleado->estado !== 'ACTIVO') {
            $this->olvidar($request);

            return null;
        }

        return $empleado;
    }

    private function olvidar(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    private function presentarEmpleado(Employee $empleado): array
    {
        return [
            'dni' => $empleado->dni,
            'apellidos_nombres' => $empleado->nombre_completo,
            'cargo' => $empleado->cargo,
        ];
    }
}
