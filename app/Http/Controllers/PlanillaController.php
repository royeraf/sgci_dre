<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeePayrollProfile;
use App\Models\EmployeeRemuneration;
use App\Models\HRContractType;
use App\Models\PlanillaBanco;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaConceptoAsignacion;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaRegimenPensionario;
use App\Services\Planilla\PlanillaGenerador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanillaController extends Controller
{
    /**
     * Página principal del apartado de Planillas y Remuneraciones.
     */
    public function index()
    {
        return Inertia::render('Planillas/Index');
    }

    // ========== REMUNERACIONES ==========

    /**
     * Régimen contractual administrado por el módulo de planillas.
     */
    private const REGIMEN_PLANILLA = 'CAS';

    /**
     * Empleados CAS activos con su remuneración base vigente y perfil de pensión.
     */
    public function getRemuneraciones()
    {
        $hoy = now()->startOfDay();

        $employees = Employee::with([
            'person',
            'position',
            'direction',
            'contractType',
            'payrollProfile.regimenPensionario',
            'payrollProfile.banco',
            'remunerations',
        ])
            ->where('estado', 'ACTIVO')
            ->whereHas('contractType', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', [self::REGIMEN_PLANILLA]);
            })
            ->get()
            ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (Employee $employee) use ($hoy) {
                $vigente = $employee->remunerations
                    ->filter(fn ($r) => $r->desde <= $hoy && (is_null($r->hasta) || $r->hasta >= $hoy))
                    ->sortByDesc('desde')
                    ->first();

                $perfil = $employee->payrollProfile;

                return [
                    'id' => $employee->id,
                    'dni' => $employee->dni,
                    'nombre_completo' => $employee->full_name,
                    'cargo' => $employee->cargo,
                    'direction' => $employee->direction_nombre,
                    'regimen' => $employee->tipo_contrato,
                    'remuneracion_base' => $vigente ? (float) $vigente->monto : null,
                    'remuneracion_id' => $vigente?->id,
                    'remuneracion_desde' => $vigente?->desde?->format('Y-m-d'),
                    'regimen_pensionario_id' => $perfil?->regimen_pensionario_id,
                    'regimen_pensionario' => $perfil?->regimenPensionario?->nombre,
                    'tipo_pension' => $perfil?->regimenPensionario?->tipo,
                    'cuspp' => $perfil?->cuspp,
                    'banco_id' => $perfil?->banco_id,
                    'banco' => $perfil?->banco?->nombre,
                    'cuenta_ahorro' => $perfil?->cuenta_ahorro,
                ];
            });

        return response()->json($employees);
    }

    /**
     * Registrar una nueva remuneración base (cierra la vigencia anterior).
     */
    public function storeRemuneracion(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'monto' => 'required|numeric|min:0',
            'tipo' => 'nullable|string|max:50',
            'desde' => 'required|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'motivo' => 'nullable|string|max:255',
        ]);

        $anterior = EmployeeRemuneration::where('employee_id', $validated['employee_id'])
            ->whereNull('hasta')
            ->orderByDesc('desde')
            ->first();

        if ($anterior && $anterior->desde < Carbon::parse($validated['desde'])->startOfDay()) {
            $anterior->update([
                'hasta' => Carbon::parse($validated['desde'])->subDay()->toDateString(),
            ]);
        }

        $remuneracion = EmployeeRemuneration::create([
            'employee_id' => $validated['employee_id'],
            'monto' => $validated['monto'],
            'tipo' => $validated['tipo'] ?? 'BASICA',
            'desde' => $validated['desde'],
            'hasta' => $validated['hasta'] ?? null,
            'motivo' => $validated['motivo'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Remuneración registrada correctamente',
            'remuneracion' => $remuneracion,
        ], 201);
    }

    /**
     * Actualizar una remuneración existente.
     */
    public function updateRemuneracion(Request $request, string $id)
    {
        $remuneracion = EmployeeRemuneration::find($id);

        if (!$remuneracion) {
            return response()->json(['message' => 'Remuneración no encontrada'], 404);
        }

        $validated = $request->validate([
            'monto' => 'sometimes|numeric|min:0',
            'tipo' => 'nullable|string|max:50',
            'desde' => 'sometimes|date',
            'hasta' => 'nullable|date',
            'motivo' => 'nullable|string|max:255',
        ]);

        $remuneracion->update($validated);

        return response()->json(['message' => 'Remuneración actualizada correctamente']);
    }

    /**
     * Eliminar una remuneración.
     */
    public function deleteRemuneracion(string $id)
    {
        $remuneracion = EmployeeRemuneration::find($id);

        if (!$remuneracion) {
            return response()->json(['message' => 'Remuneración no encontrada'], 404);
        }

        $remuneracion->delete();

        return response()->json(['message' => 'Remuneración eliminada correctamente']);
    }

    // ========== ASIGNACIONES DE CONCEPTOS ==========

    /**
     * Asignaciones de conceptos (opcionalmente filtradas por concepto).
     */
    public function getAsignaciones(Request $request)
    {
        $query = PlanillaConceptoAsignacion::with(['concepto', 'employee.person', 'contractType']);

        if ($request->filled('concepto_id')) {
            $query->where('concepto_id', $request->concepto_id);
        }

        $asignaciones = $query->orderByDesc('created_at')
            ->get()
            ->map(function (PlanillaConceptoAsignacion $asignacion) {
                $destino = $asignacion->employee_id ? 'EMPLEADO' : 'REGIMEN';

                return [
                    'id' => $asignacion->id,
                    'concepto_id' => $asignacion->concepto_id,
                    'concepto' => $asignacion->concepto?->nombre,
                    'employee_id' => $asignacion->employee_id,
                    'contract_type_id' => $asignacion->contract_type_id,
                    'destino' => $destino,
                    'destino_nombre' => $destino === 'EMPLEADO'
                        ? $asignacion->employee?->full_name
                        : $asignacion->contractType?->nombre,
                    'monto' => $asignacion->monto !== null ? (float) $asignacion->monto : null,
                    'porcentaje' => $asignacion->porcentaje !== null ? (float) $asignacion->porcentaje : null,
                    'desde' => $asignacion->desde?->format('Y-m-d'),
                    'hasta' => $asignacion->hasta?->format('Y-m-d'),
                    'activo' => (bool) $asignacion->activo,
                ];
            });

        return response()->json($asignaciones);
    }

    /**
     * Parámetros para asignar: regímenes contractuales y empleados CAS activos.
     */
    public function getAsignacionParametros()
    {
        $contractTypes = HRContractType::orderBy('nombre')->get(['id', 'nombre']);

        $employees = Employee::with('person')
            ->where('estado', 'ACTIVO')
            ->whereHas('contractType', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', [self::REGIMEN_PLANILLA]);
            })
            ->get()
            ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'dni' => $employee->dni,
                'nombre_completo' => $employee->full_name,
            ]);

        return response()->json([
            'contract_types' => $contractTypes,
            'employees' => $employees,
        ]);
    }

    public function storeAsignacion(Request $request)
    {
        $validated = $this->validateAsignacion($request);

        $asignacion = PlanillaConceptoAsignacion::create($validated);

        return response()->json([
            'message' => 'Asignación registrada correctamente',
            'asignacion' => $asignacion,
        ], 201);
    }

    public function updateAsignacion(Request $request, string $id)
    {
        $asignacion = PlanillaConceptoAsignacion::find($id);

        if (!$asignacion) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        $validated = $request->validate([
            'monto' => 'nullable|numeric|min:0',
            'porcentaje' => 'nullable|numeric|min:0',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'activo' => 'boolean',
        ]);

        $asignacion->update($validated);

        return response()->json(['message' => 'Asignación actualizada correctamente']);
    }

    public function deleteAsignacion(string $id)
    {
        $asignacion = PlanillaConceptoAsignacion::find($id);

        if (!$asignacion) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }

        $asignacion->delete();

        return response()->json(['message' => 'Asignación eliminada correctamente']);
    }

    private function validateAsignacion(Request $request, ?string $id = null): array
    {
        return $request->validate([
            'concepto_id' => 'required|exists:planilla_conceptos,id',
            'employee_id' => 'nullable|required_without:contract_type_id|exists:employees,id',
            'contract_type_id' => 'nullable|required_without:employee_id|exists:hr_contract_types,id',
            'monto' => 'nullable|numeric|min:0',
            'porcentaje' => 'nullable|numeric|min:0',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde',
            'activo' => 'boolean',
        ]);
    }

    // ========== PERFIL DE PLANILLA ==========

    /**
     * Crear/actualizar el perfil de planilla (pensión, CUSPP, cuenta).
     */
    public function updatePerfil(Request $request, string $employeeId)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $validated = $request->validate([
            'regimen_pensionario_id' => 'nullable|exists:planilla_regimenes_pensionarios,id',
            'cuspp' => 'nullable|string|max:30',
            'banco_id' => 'nullable|exists:planilla_bancos,id',
            'cuenta_ahorro' => 'nullable|string|max:50',
        ]);

        $perfil = EmployeePayrollProfile::updateOrCreate(
            ['employee_id' => $employeeId],
            $validated
        );

        return response()->json([
            'message' => 'Perfil de planilla actualizado correctamente',
            'perfil' => $perfil,
        ]);
    }

    // ========== CATÁLOGOS ==========

    public function getConceptos()
    {
        $conceptos = PlanillaConcepto::withCount('asignaciones')
            ->orderBy('tipo')
            ->orderBy('orden')
            ->get();

        return response()->json($conceptos);
    }

    /**
     * Crear un concepto de planilla (ingreso, descuento o aportación).
     */
    public function storeConcepto(Request $request)
    {
        $validated = $this->validateConcepto($request);

        $concepto = PlanillaConcepto::create($validated);

        return response()->json([
            'message' => 'Concepto registrado correctamente',
            'concepto' => $concepto,
        ], 201);
    }

    /**
     * Actualizar un concepto existente.
     */
    public function updateConcepto(Request $request, string $id)
    {
        $concepto = PlanillaConcepto::find($id);

        if (!$concepto) {
            return response()->json(['message' => 'Concepto no encontrado'], 404);
        }

        $validated = $this->validateConcepto($request, $id);
        $concepto->update($validated);

        return response()->json([
            'message' => 'Concepto actualizado correctamente',
            'concepto' => $concepto,
        ]);
    }

    /**
     * Eliminar un concepto (bloquea si tiene asignaciones).
     */
    public function deleteConcepto(string $id)
    {
        $concepto = PlanillaConcepto::find($id);

        if (!$concepto) {
            return response()->json(['message' => 'Concepto no encontrado'], 404);
        }

        if ($concepto->asignaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: el concepto tiene asignaciones registradas',
            ], 422);
        }

        $concepto->delete();

        return response()->json(['message' => 'Concepto eliminado correctamente']);
    }

    private function validateConcepto(Request $request, ?string $id = null): array
    {
        return $request->validate([
            'codigo' => 'required|string|max:50|unique:planilla_conceptos,codigo' . ($id ? ',' . $id : ''),
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|in:INGRESO,DESCUENTO,APORTACION',
            'categoria' => 'nullable|string|max:50',
            'afecto_renta5' => 'boolean',
            'afecto_essalud' => 'boolean',
            'afecto_onp' => 'boolean',
            'afecto_afp' => 'boolean',
            'es_porcentaje' => 'boolean',
            'valor' => 'nullable|numeric|min:0',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ]);
    }

    public function getRegimenes()
    {
        $regimenes = PlanillaRegimenPensionario::activos()
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        return response()->json($regimenes);
    }

    // ========== BANCOS ==========

    public function getBancos()
    {
        $bancos = PlanillaBanco::orderBy('nombre')->get();

        return response()->json($bancos);
    }

    public function storeBanco(Request $request)
    {
        $validated = $this->validateBanco($request);

        $banco = PlanillaBanco::create($validated);

        return response()->json([
            'message' => 'Banco registrado correctamente',
            'banco' => $banco,
        ], 201);
    }

    public function updateBanco(Request $request, string $id)
    {
        $banco = PlanillaBanco::find($id);

        if (!$banco) {
            return response()->json(['message' => 'Banco no encontrado'], 404);
        }

        $validated = $this->validateBanco($request, $id);
        $banco->update($validated);

        return response()->json([
            'message' => 'Banco actualizado correctamente',
            'banco' => $banco,
        ]);
    }

    public function deleteBanco(string $id)
    {
        $banco = PlanillaBanco::find($id);

        if (!$banco) {
            return response()->json(['message' => 'Banco no encontrado'], 404);
        }

        if ($banco->payrollProfiles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: el banco está asignado a uno o más empleados',
            ], 422);
        }

        $banco->delete();

        return response()->json(['message' => 'Banco eliminado correctamente']);
    }

    private function validateBanco(Request $request, ?string $id = null): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:100|unique:planilla_bancos,nombre' . ($id ? ',' . $id : ''),
            'codigo' => 'nullable|string|max:20',
            'activo' => 'boolean',
        ]);
    }

    // ========== PERIODOS ==========

    public function getPeriodos()
    {
        $periodos = PlanillaPeriodo::orderByDesc('anio')->orderByDesc('mes')->get();

        return response()->json($periodos);
    }

    public function storePeriodo(Request $request)
    {
        $validated = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        $existe = PlanillaPeriodo::where('anio', $validated['anio'])
            ->where('mes', $validated['mes'])
            ->exists();

        if ($existe) {
            return response()->json(['message' => 'Ya existe una planilla para ese periodo'], 422);
        }

        $inicio = Carbon::create($validated['anio'], $validated['mes'], 1)->startOfMonth();

        $periodo = PlanillaPeriodo::create([
            'anio' => $validated['anio'],
            'mes' => $validated['mes'],
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $inicio->copy()->endOfMonth()->toDateString(),
            'estado' => 'BORRADOR',
        ]);

        return response()->json([
            'message' => 'Periodo creado correctamente',
            'periodo' => $periodo,
        ], 201);
    }

    public function generarPeriodo(string $id, PlanillaGenerador $generador)
    {
        $periodo = PlanillaPeriodo::find($id);

        if (!$periodo) {
            return response()->json(['message' => 'Periodo no encontrado'], 404);
        }

        try {
            $resumen = $generador->generar($periodo);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(array_merge([
            'message' => 'Planilla generada correctamente',
        ], $resumen));
    }

    public function getPeriodoDetalle(string $id)
    {
        $periodo = PlanillaPeriodo::find($id);

        if (!$periodo) {
            return response()->json(['message' => 'Periodo no encontrado'], 404);
        }

        $detalles = $periodo->detalles()
            ->with(['employee.person', 'items'])
            ->get()
            ->sortBy(fn ($d) => $d->employee?->apellidos, SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn ($d) => [
                'id' => $d->id,
                'employee_id' => $d->employee_id,
                'dni' => $d->employee?->dni,
                'nombre_completo' => $d->employee?->full_name,
                'remuneracion_base' => (float) $d->remuneracion_base,
                'total_ingresos' => (float) $d->total_ingresos,
                'total_descuentos' => (float) $d->total_descuentos,
                'total_aportaciones' => (float) $d->total_aportaciones,
                'neto_pagar' => (float) $d->neto_pagar,
                'items' => $d->items->map(fn ($item) => [
                    'id' => $item->id,
                    'tipo' => $item->tipo,
                    'descripcion' => $item->descripcion,
                    'base_calculo' => (float) $item->base_calculo,
                    'porcentaje' => $item->porcentaje !== null ? (float) $item->porcentaje : null,
                    'monto' => (float) $item->monto,
                ])->values(),
            ]);

        return response()->json([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
                'editable' => $periodo->editable,
                'total_empleados' => $periodo->total_empleados,
                'total_neto' => (float) $periodo->total_neto,
            ],
            'detalles' => $detalles,
        ]);
    }

    public function deletePeriodo(string $id)
    {
        $periodo = PlanillaPeriodo::find($id);

        if (!$periodo) {
            return response()->json(['message' => 'Periodo no encontrado'], 404);
        }

        if (!$periodo->editable) {
            return response()->json(['message' => 'No se puede eliminar una planilla cerrada'], 422);
        }

        $periodo->detalles()->each(fn ($detalle) => $detalle->items()->delete());
        $periodo->detalles()->delete();
        $periodo->delete();

        return response()->json(['message' => 'Periodo eliminado correctamente']);
    }

    public function getSummary()
    {
        $personal = Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', [self::REGIMEN_PLANILLA]);
            })
            ->count();

        $ultimo = PlanillaPeriodo::orderByDesc('anio')->orderByDesc('mes')->first();

        return response()->json([
            'periodo_actual' => $ultimo?->nombre_periodo,
            'boletas_emitidas' => 0,
            'personal_en_planilla' => $personal,
            'pendientes' => PlanillaPeriodo::where('estado', 'BORRADOR')->count(),
        ]);
    }
}
