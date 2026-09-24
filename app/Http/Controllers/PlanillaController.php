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
use App\Models\PlanillaTardanza;
use App\Services\Planilla\PlanillaGenerador;
use App\Services\Planilla\TardanzaService;
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
                    'fecha_ingreso' => $employee->fecha_ingreso?->format('Y-m-d'),
                    'fecha_inicio_contrato' => $employee->fecha_inicio_contrato?->format('Y-m-d'),
                    'fecha_fin_contrato' => $employee->fecha_fin_contrato?->format('Y-m-d'),
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

    /**
     * Administra las fechas de contrato del empleado. Fin nulo = indeterminado.
     */
    public function updateContrato(Request $request, string $employeeId)
    {
        $employee = Employee::find($employeeId);

        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $validated = $request->validate([
            'fecha_inicio_contrato' => 'required|date',
            'fecha_fin_contrato' => 'nullable|date|after_or_equal:fecha_inicio_contrato',
        ]);

        $employee->update([
            'fecha_inicio_contrato' => $validated['fecha_inicio_contrato'],
            'fecha_fin_contrato' => $validated['fecha_fin_contrato'] ?? null,
        ]);

        return response()->json([
            'message' => 'Fechas de contrato actualizadas correctamente',
            'fecha_inicio_contrato' => $employee->fecha_inicio_contrato?->format('Y-m-d'),
            'fecha_fin_contrato' => $employee->fecha_fin_contrato?->format('Y-m-d'),
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

    public function getRegimenes(Request $request)
    {
        $query = PlanillaRegimenPensionario::orderBy('tipo')->orderBy('nombre');

        // El select del perfil pide solo activos; el catálogo administrable pide todos.
        if (!$request->boolean('todos')) {
            $query->activos();
        }

        return response()->json($query->get());
    }

    public function storeRegimen(Request $request)
    {
        $regimen = PlanillaRegimenPensionario::create($this->validateRegimen($request));

        return response()->json([
            'message' => 'Régimen registrado correctamente',
            'regimen' => $regimen,
        ], 201);
    }

    public function updateRegimen(Request $request, string $id)
    {
        $regimen = PlanillaRegimenPensionario::find($id);

        if (!$regimen) {
            return response()->json(['message' => 'Régimen no encontrado'], 404);
        }

        $regimen->update($this->validateRegimen($request, $id));

        return response()->json([
            'message' => 'Régimen actualizado correctamente',
            'regimen' => $regimen,
        ]);
    }

    public function deleteRegimen(string $id)
    {
        $regimen = PlanillaRegimenPensionario::find($id);

        if (!$regimen) {
            return response()->json(['message' => 'Régimen no encontrado'], 404);
        }

        if ($regimen->payrollProfiles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: el régimen está asignado a uno o más empleados',
            ], 422);
        }

        $regimen->delete();

        return response()->json(['message' => 'Régimen eliminado correctamente']);
    }

    private function validateRegimen(Request $request, ?string $id = null): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:100|unique:planilla_regimenes_pensionarios,nombre' . ($id ? ',' . $id : ''),
            'tipo' => 'required|in:AFP,ONP',
            'aporte_obligatorio' => 'required|numeric|between:0,1',
            'prima_seguro' => 'nullable|numeric|between:0,1',
            'comision_fija' => 'nullable|numeric|between:0,1',
            'comision_mixta' => 'nullable|numeric|between:0,1',
            'comision_flujo' => 'nullable|numeric|between:0,1',
            'activo' => 'boolean',
        ]);
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

    // ========== TARDANZAS (hoja «Dscto. Tard.») ==========

    /**
     * Filas del periodo al estilo del Excel: una por empleado CAS, con los
     * importes de la hoja (E, F, G, H, I, J, K, L, N) y sus registros diarios.
     */
    public function getTardanzas(Request $request, PlanillaGenerador $generador)
    {
        $validated = $request->validate([
            'periodo_id' => 'required|string|exists:planilla_periodos,id',
        ]);

        $periodo = PlanillaPeriodo::findOrFail($validated['periodo_id']);
        $cierre = $periodo->fechaCierre();

        $registros = PlanillaTardanza::with('employee.person')
            ->where('periodo_id', $periodo->id)
            ->orderBy('fecha')
            ->get()
            ->groupBy('employee_id');

        $conceptos = PlanillaConcepto::where('activo', true)->get()->keyBy('id');

        $empleados = Employee::with('person', 'remunerations')
            ->where('estado', 'ACTIVO')
            ->whereHas('contractType', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', [self::REGIMEN_PLANILLA]);
            })
            ->get()
            ->filter(fn (Employee $empleado) => $generador->remuneracionVigente($empleado, $cierre) > 0)
            ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $filas = $empleados->map(function (Employee $empleado) use ($registros, $generador, $cierre, $conceptos) {
            $items = $registros->get($empleado->id) ?? collect();
            $vigentes = $items->where('justificado', false);

            // E = REMUNERACIONES (ingresos vigentes al cierre)
            $ingresos = $generador->ingresosVigentes($empleado, $cierre, $conceptos);

            // F / G: snapshot del primer registro; si no hay, previsualización.
            $valorDia = $items->first()
                ? (float) $items->first()->valor_dia
                : round($ingresos / 30, 2);
            $valorMinuto = $items->first()
                ? (float) $items->first()->valor_minuto
                : round($valorDia / 480, 2);

            $dias = (int) $vigentes->sum('dias');
            $minutos = (int) $vigentes->sum('minutos');
            $montoDias = round((float) $vigentes->sum('monto_dias'), 2);
            $montoMinutos = round((float) $vigentes->sum('monto_minutos'), 2);
            $total = round($montoDias + $montoMinutos, 2);

            return [
                'employee_id' => $empleado->id,
                'dni' => $empleado->dni,
                'nombre_completo' => $empleado->full_name,
                'remuneraciones' => $ingresos,
                'valor_dia' => $valorDia,
                'valor_minuto' => $valorMinuto,
                'dias' => $dias,
                'minutos' => $minutos,
                'monto_dias' => $montoDias,
                'monto_minutos' => $montoMinutos,
                'total' => $total,
                'base_imponible' => round($ingresos - $total, 2),
                'con_registros' => $items->isNotEmpty(),
                'registros' => $items->map(fn ($r) => [
                    'id' => $r->id,
                    'fecha' => $r->fecha->toDateString(),
                    'dias' => (int) $r->dias,
                    'minutos' => (int) $r->minutos,
                    'monto_dias' => (float) $r->monto_dias,
                    'monto_minutos' => (float) $r->monto_minutos,
                    'total' => (float) $r->total,
                    'justificado' => (bool) $r->justificado,
                    'observacion' => $r->observacion,
                    'origen' => $r->origen,
                ])->values(),
            ];
        });

        return response()->json([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
                'editable' => $periodo->editable,
                'fecha_inicio' => $periodo->fecha_inicio?->toDateString(),
                'fecha_fin' => $periodo->fecha_fin?->toDateString(),
            ],
            'filas' => $filas,
        ]);
    }

    public function storeTardanza(Request $request, TardanzaService $service)
    {
        $validated = $this->validateTardanza($request);

        $periodo = PlanillaPeriodo::findOrFail($validated['periodo_id']);

        if (!$periodo->editable) {
            return response()->json([
                'message' => "La planilla está en estado {$periodo->estado} y no admite cambios",
            ], 422);
        }

        $tardanza = $service->registrar(
            $periodo,
            Employee::findOrFail($validated['employee_id']),
            Carbon::parse($validated['fecha']),
            $validated['dias'],
            $validated['minutos'],
            $validated['observacion'] ?? null
        );

        return response()->json([
            'message' => 'Tardanza registrada correctamente',
            'tardanza' => $tardanza,
        ], 201);
    }

    public function updateTardanza(Request $request, string $id, TardanzaService $service)
    {
        $tardanza = PlanillaTardanza::find($id);

        if (!$tardanza) {
            return response()->json(['message' => 'Registro de tardanza no encontrado'], 404);
        }

        if (!$tardanza->periodo->editable) {
            return response()->json([
                'message' => "La planilla está en estado {$tardanza->periodo->estado} y no admite cambios",
            ], 422);
        }

        $validated = $request->validate([
            'dias' => 'sometimes|integer|min:0|max:31',
            'minutos' => 'sometimes|integer|min:0|max:1440',
            'observacion' => 'nullable|string|max:255',
            'justificado' => 'sometimes|boolean',
        ]);

        $cambioImportes = array_key_exists('dias', $validated) || array_key_exists('minutos', $validated);

        $campos = [];
        if (array_key_exists('observacion', $validated)) {
            $campos['observacion'] = $validated['observacion'];
        }
        if (array_key_exists('justificado', $validated)) {
            $campos['justificado'] = $validated['justificado'];
        }
        if ($cambioImportes) {
            foreach (['dias', 'minutos'] as $campo) {
                if (array_key_exists($campo, $validated)) {
                    $campos[$campo] = $validated[$campo];
                }
            }
        }

        $tardanza->update($campos);

        if ($cambioImportes) {
            $service->recalcular($tardanza);
        }

        return response()->json([
            'message' => 'Registro actualizado correctamente',
            'tardanza' => $tardanza->fresh(),
        ]);
    }

    public function deleteTardanza(string $id)
    {
        $tardanza = PlanillaTardanza::find($id);

        if (!$tardanza) {
            return response()->json(['message' => 'Registro de tardanza no encontrado'], 404);
        }

        if (!$tardanza->periodo->editable) {
            return response()->json([
                'message' => "La planilla está en estado {$tardanza->periodo->estado} y no admite cambios",
            ], 422);
        }

        $tardanza->delete();

        return response()->json(['message' => 'Registro eliminado correctamente']);
    }

    private function validateTardanza(Request $request): array
    {
        $validated = $request->validate([
            'periodo_id' => 'required|string|exists:planilla_periodos,id',
            'employee_id' => 'required|string|exists:employees,id',
            'fecha' => 'required|date',
            'dias' => 'required|integer|min:0|max:31',
            'minutos' => 'required|integer|min:0|max:1440',
            'observacion' => 'nullable|string|max:255',
        ]);

        if ($validated['dias'] === 0 && $validated['minutos'] === 0) {
            abort(response()->json(['message' => 'Ingrese días o minutos de tardanza'], 422));
        }

        $periodo = PlanillaPeriodo::findOrFail($validated['periodo_id']);
        $fecha = Carbon::parse($validated['fecha'])->toDateString();

        if ($fecha < $periodo->fecha_inicio?->toDateString() || $fecha > $periodo->fecha_fin?->toDateString()) {
            abort(response()->json(['message' => 'La fecha debe estar dentro del periodo'], 422));
        }

        return $validated;
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
                'fecha_inicio' => $periodo->fecha_inicio?->toDateString(),
                'fecha_fin' => $periodo->fecha_fin?->toDateString(),
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
        $ultimo = PlanillaPeriodo::orderByDesc('anio')->orderByDesc('mes')->first();
        $fecha = $ultimo?->fechaCierre() ?? Carbon::now();

        $personal = Employee::where('estado', 'ACTIVO')
            ->whereHas('contractType', function ($query) {
                $query->whereRaw('UPPER(nombre) = ?', [self::REGIMEN_PLANILLA]);
            })
            ->whereHas('remunerations', function ($query) use ($fecha) {
                $query->where('monto', '>', 0)
                    ->where('desde', '<=', $fecha)
                    ->where(function ($sub) use ($fecha) {
                        $sub->whereNull('hasta')->orWhere('hasta', '>=', $fecha);
                    });
            })
            ->count();

        return response()->json([
            'periodo_actual' => $ultimo?->nombre_periodo,
            'boletas_emitidas' => 0,
            'personal_en_planilla' => $personal,
            'pendientes' => PlanillaPeriodo::where('estado', 'BORRADOR')->count(),
        ]);
    }
}
