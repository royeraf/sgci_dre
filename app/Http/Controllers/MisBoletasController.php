<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Services\Planilla\BoletaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Apartado "Mis Boletas" del sistema principal: el trabajador autenticado
 * consulta sus propias boletas de los periodos visibles, descarga el PDF y
 * confirma que las revisó, sin necesidad de los permisos del módulo admin
 * de planillas (`role:ROL001,ROL009`).
 *
 * Reglas de seguridad:
 *  - La identidad es el usuario autenticado: solo se sirven los detalles
 *    cuyo `employee_id` coincide con el empleado vinculado (`person_id`).
 *  - Solo periodos visibles fuera del módulo
 *    ({@see PlanillaPeriodo::ESTADOS_BOLETA_VISIBLE}).
 *  - El portal público por DNI (`/boletas`) se mantiene intacto.
 */
class MisBoletasController extends Controller
{
    public function __construct(private BoletaService $boletas)
    {
    }

    /**
     * Página del apartado con las boletas del trabajador autenticado.
     */
    public function index(Request $request)
    {
        $empleado = $this->empleadoPropio($request);
        $boletas = $empleado ? $this->boletas->listarPorEmpleado($empleado) : [];

        return Inertia::render('MisBoletas/Index', [
            'empleado' => $empleado ? [
                'dni' => $empleado->dni,
                'apellidos_nombres' => $empleado->nombre_completo,
                'cargo' => $empleado->cargo,
            ] : null,
            'boletas' => $boletas,
            'sin_vinculo' => $empleado === null,
        ]);
    }

    /**
     * Datos de una boleta propia para la vista previa en pantalla.
     */
    public function show(Request $request, PlanillaDetalle $detalle)
    {
        if (!$this->detallePropio($request, $detalle)) {
            return response()->json(['message' => 'Boleta no disponible.'], 404);
        }

        return response()->json($this->boletas->armar($detalle));
    }

    /**
     * PDF de una boleta propia (mismo documento que el módulo admin).
     */
    public function pdf(Request $request, PlanillaDetalle $detalle)
    {
        if (!$this->detallePropio($request, $detalle)) {
            return redirect()->route('mis-boletas.index');
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
            return response()->json(['message' => 'Boleta no disponible.'], 404);
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
     * Empleado vinculado a la cuenta autenticada (comparten `person_id`).
     */
    private function empleadoPropio(Request $request): ?Employee
    {
        $user = $request->user();

        if (!$user?->person_id) {
            return null;
        }

        return Employee::where('person_id', $user->person_id)->first();
    }

    /**
     * El detalle existe, pertenece al trabajador autenticado y su periodo es
     * visible fuera del módulo. Si no, el caller responde 404/redirect.
     */
    private function detallePropio(Request $request, PlanillaDetalle $detalle): bool
    {
        $empleado = $this->empleadoPropio($request);

        if (!$empleado || $detalle->employee_id !== $empleado->id) {
            return false;
        }

        $periodo = $detalle->periodo;

        return $periodo !== null
            && in_array($periodo->estado, PlanillaPeriodo::ESTADOS_BOLETA_VISIBLE, true);
    }
}
