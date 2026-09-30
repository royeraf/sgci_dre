<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Models\EmployeePayrollProfile;
use App\Models\EmployeeRemuneration;
use App\Models\HRContractType;
use App\Models\HRPosition;
use App\Models\HrDirection;
use App\Models\HrOffice;
use App\Models\Person;
use App\Models\PlanillaBanco;
use App\Models\PlanillaComisionAfp;
use App\Models\PlanillaConcepto;
use App\Models\PlanillaConceptoAsignacion;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaParametro;
use App\Models\PlanillaParametroAfp;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaRegimenPensionario;
use App\Models\PlanillaTardanza;
use App\Services\Planilla\PlanillaGenerador;
use App\Services\Planilla\TardanzaService;
use App\Services\ReniecService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
            ->withCount(['conceptoAsignaciones as asignaciones_count'])
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
                    'tipo_comision' => $perfil?->tipo_comision,
                    'banco_id' => $perfil?->banco_id,
                    'banco' => $perfil?->banco?->nombre,
                    'cuenta_ahorro' => $perfil?->cuenta_ahorro,
                    'fecha_nacimiento' => $employee->person?->fecha_nacimiento?->format('Y-m-d'),
                    'modalidad_cas' => $employee->modalidad_cas,
                    'modalidad_cas_efectiva' => $employee->modalidadCas(),
                    'asignaciones_count' => (int) ($employee->asignaciones_count ?? 0),
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
     * Asignaciones de conceptos (opcionalmente filtradas por concepto o empleado).
     */
    public function getAsignaciones(Request $request)
    {
        $query = PlanillaConceptoAsignacion::with(['concepto', 'employee.person', 'contractType']);

        if ($request->filled('concepto_id')) {
            $query->where('concepto_id', $request->concepto_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
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
                    'anio' => $asignacion->anio !== null ? (int) $asignacion->anio : null,
                    'mes' => $asignacion->mes !== null ? (int) $asignacion->mes : null,
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
            'anio' => 'nullable|integer|min:2000|max:2100',
            'mes' => 'nullable|integer|min:1|max:12',
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
            'anio' => 'nullable|required_with:mes|integer|min:2000|max:2100',
            'mes' => 'nullable|required_with:anio|integer|min:1|max:12',
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
            'tipo_comision' => 'nullable|in:FLUJO,MIXTA,SALDO',
            'banco_id' => 'nullable|exists:planilla_bancos,id',
            'cuenta_ahorro' => 'nullable|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
        ]);

        $fechaNacimiento = $validated['fecha_nacimiento'] ?? null;
        unset($validated['fecha_nacimiento']);

        $perfil = EmployeePayrollProfile::updateOrCreate(
            ['employee_id' => $employeeId],
            $validated
        );

        if ($request->has('fecha_nacimiento')) {
            $employee->person?->update(['fecha_nacimiento' => $fechaNacimiento]);
        }

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
            'modalidad_cas' => 'nullable|in:INDETERMINADO,TRANSITORIO',
        ]);

        $employee->update([
            'fecha_inicio_contrato' => $validated['fecha_inicio_contrato'],
            'fecha_fin_contrato' => $validated['fecha_fin_contrato'] ?? null,
            'modalidad_cas' => $validated['modalidad_cas'] ?? $employee->modalidad_cas,
        ]);

        return response()->json([
            'message' => 'Fechas de contrato actualizadas correctamente',
            'fecha_inicio_contrato' => $employee->fecha_inicio_contrato?->format('Y-m-d'),
            'fecha_fin_contrato' => $employee->fecha_fin_contrato?->format('Y-m-d'),
            'modalidad_cas' => $employee->modalidad_cas,
            'modalidad_cas_efectiva' => $employee->modalidadCas(),
        ]);
    }

    /**
     * Consulta de DNI para el alta de empleados: detecta si ya está registrado
     * como empleado y, si no, resuelve nombres/apellidos (local o RENIEC).
     */
    public function consultarDniEmpleado(Request $request, ReniecService $reniecService)
    {
        $request->validate([
            'dni' => 'required|string|size:8',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
        ]);

        $persona = Person::with('employee')->where('dni', $request->dni)->first();
        $empleado = $persona?->employee;

        if ($empleado) {
            return response()->json([
                'success' => false,
                'registrado' => true,
                'message' => 'El DNI ya está registrado como empleado.',
                'empleado' => [
                    'id' => $empleado->id,
                    'nombre_completo' => $empleado->full_name,
                    'estado' => $empleado->estado,
                ],
                'data' => null,
            ]);
        }

        if ($persona && ($persona->nombres || $persona->apellidos)) {
            return response()->json([
                'success' => true,
                'registrado' => false,
                'message' => 'Datos encontrados en registro local',
                'data' => [
                    'dni' => $persona->dni,
                    'nombres' => $persona->nombres,
                    'apellido_paterno' => $persona->apellidos,
                    'apellido_materno' => '',
                    'nombre_completo' => trim($persona->nombres . ' ' . $persona->apellidos),
                ],
            ]);
        }

        $resultado = $reniecService->consultarDni($request->dni);

        return response()->json([
            'success' => $resultado['success'],
            'registrado' => false,
            'message' => $resultado['message'],
            'data' => $resultado['data'],
        ]);
    }

    /**
     * Alta completa de un empleado CAS desde Remuneraciones:
     * persona + empleado + remuneración base + (opcional) perfil de pensión.
     */
    public function storeEmpleado(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:M,F,Masculino,Femenino',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'cargo_id' => 'nullable|exists:hr_positions,id',
            'direccion_id' => 'nullable|exists:hr_directions,id',
            'office_id' => 'nullable|exists:hr_offices,id',
            'contract_type_id' => 'required|exists:hr_contract_types,id',
            'fecha_ingreso' => 'required|date',
            'observaciones' => 'nullable|string',
            'remuneracion' => 'required|numeric|min:0.01',
            'remuneracion_desde' => 'required|date',
            'modalidad_cas' => 'required|in:INDETERMINADO,TRANSITORIO',
            'fecha_inicio_contrato' => 'required|date',
            'fecha_fin_contrato' => 'nullable|date|after_or_equal:fecha_inicio_contrato',
            'regimen_pensionario_id' => 'nullable|exists:planilla_regimenes_pensionarios,id',
            'cuspp' => 'nullable|string|max:30',
            'tipo_comision' => 'nullable|in:FLUJO,MIXTA,SALDO',
            'banco_id' => 'nullable|exists:planilla_bancos,id',
            'cuenta_ahorro' => 'nullable|string|max:50',
        ]);

        $persona = Person::firstOrNew(['dni' => $validated['dni']]);

        if ($persona->exists && $persona->employee) {
            return response()->json(['message' => 'Esta persona ya está registrada como empleado.'], 422);
        }

        $persona->nombres = $validated['nombres'];
        $persona->apellidos = $validated['apellidos'];
        $persona->fecha_nacimiento = $validated['fecha_nacimiento'] ?? $persona->fecha_nacimiento;
        if (!empty($validated['genero'])) {
            $persona->genero = in_array($validated['genero'], ['M', 'Masculino']) ? 'Masculino' : 'Femenino';
        }
        $persona->direccion = $validated['direccion'] ?? $persona->direccion;
        $persona->telefono = $validated['telefono'] ?? $persona->telefono;
        $persona->email = $validated['correo'] ?? $persona->email;
        $persona->tipo = 'INTERNO';
        $persona->is_active = true;

        $empleado = DB::transaction(function () use ($validated, $persona) {
            $persona->save();

            $empleado = Employee::create([
                'person_id' => $persona->id,
                'position_id' => $validated['cargo_id'] ?? null,
                'direction_id' => $validated['direccion_id'] ?? null,
                'office_id' => $validated['office_id'] ?? null,
                'contract_type_id' => $validated['contract_type_id'],
                'fecha_ingreso' => $validated['fecha_ingreso'],
                'fecha_inicio_contrato' => $validated['fecha_inicio_contrato'],
                'fecha_fin_contrato' => $validated['fecha_fin_contrato'] ?? null,
                'modalidad_cas' => $validated['modalidad_cas'],
                'estado' => 'ACTIVO',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            EmployeeRemuneration::create([
                'employee_id' => $empleado->id,
                'monto' => $validated['remuneracion'],
                'tipo' => 'BASICA',
                'desde' => $validated['remuneracion_desde'],
                'motivo' => 'Alta desde Remuneraciones',
                'created_by' => auth()->id(),
            ]);

            if (!empty($validated['regimen_pensionario_id']) || !empty($validated['banco_id'])) {
                EmployeePayrollProfile::create([
                    'employee_id' => $empleado->id,
                    'regimen_pensionario_id' => $validated['regimen_pensionario_id'] ?? null,
                    'cuspp' => $validated['cuspp'] ?? null,
                    'tipo_comision' => $validated['tipo_comision'] ?? null,
                    'banco_id' => $validated['banco_id'] ?? null,
                    'cuenta_ahorro' => $validated['cuenta_ahorro'] ?? null,
                ]);
            }

            return $empleado;
        });

        return response()->json([
            'message' => 'Empleado registrado correctamente. Vuelva a generar la planilla para incluirlo.',
            'employee_id' => $empleado->id,
        ], 201);
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
            'activo' => 'boolean',
        ]);
    }

    // ========== PARÁMETROS DE PLANILLA (UIT / tope EsSalud) ==========

    public function getParametros()
    {
        $parametros = PlanillaParametro::orderBy('anio')->get()->map(fn ($p) => [
            'id' => $p->id,
            'anio' => $p->anio,
            'uit' => (float) $p->uit,
            'pct_tope_essalud' => (float) $p->pct_tope_essalud,
            'rmv' => $p->rmv !== null ? (float) $p->rmv : null,
            'tasa_essalud' => (float) $p->tasa_essalud,
            'tope_essalud' => $p->topeEssalud(),
            'activo' => $p->activo,
        ]);

        return response()->json($parametros);
    }

    public function updateParametro(Request $request, string $id)
    {
        $parametro = PlanillaParametro::find($id);

        if (!$parametro) {
            return response()->json(['message' => 'Parámetro no encontrado'], 404);
        }

        $validated = $request->validate([
            'uit' => 'required|numeric|min:1',
            'pct_tope_essalud' => 'required|numeric|min:0|max:1',
            'rmv' => 'nullable|numeric|min:0',
            'tasa_essalud' => 'required|numeric|min:0|max:1',
            'activo' => 'required|boolean',
        ]);

        $parametro->update($validated);

        return response()->json(['message' => 'Parámetro actualizado correctamente']);
    }

    // ========== PARÁMETROS SBS AFP (aporte / prima / RMA / comisiones) ==========

    public function getParametrosAfp()
    {
        $parametros = PlanillaParametroAfp::orderBy('mes')->get()->map(fn ($p) => [
            'id' => $p->id,
            'mes' => $p->mes->format('Y-m-d'),
            'aporte_obligatorio' => (float) $p->aporte_obligatorio,
            'prima_seguro' => (float) $p->prima_seguro,
            'remuneracion_maxima_asegurable' => (float) $p->remuneracion_maxima_asegurable,
        ]);

        $regimenes = PlanillaRegimenPensionario::where('tipo', 'AFP')->orderBy('nombre')->get();

        $comisiones = PlanillaComisionAfp::orderBy('mes')->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'mes' => $c->mes->format('Y-m-d'),
                'regimen_pensionario_id' => $c->regimen_pensionario_id,
                'regimen' => $regimenes->firstWhere('id', $c->regimen_pensionario_id)?->nombre,
                'comision_flujo' => (float) $c->comision_flujo,
                'comision_saldo' => (float) $c->comision_saldo,
            ]);

        return response()->json([
            'parametros' => $parametros,
            'comisiones' => $comisiones,
            'regimenes' => $regimenes->map(fn ($r) => [
                'id' => $r->id,
                'nombre' => $r->nombre,
            ]),
        ]);
    }

    public function updateParametroAfp(Request $request, string $id)
    {
        $parametro = PlanillaParametroAfp::find($id);

        if (!$parametro) {
            return response()->json(['message' => 'Parámetro AFP no encontrado'], 404);
        }

        $validated = $request->validate([
            'aporte_obligatorio' => 'required|numeric|between:0,1',
            'prima_seguro' => 'required|numeric|between:0,1',
            'remuneracion_maxima_asegurable' => 'required|numeric|min:0',
        ]);

        $parametro->update($validated);

        return response()->json(['message' => 'Parámetro AFP actualizado correctamente']);
    }

    public function updateComisionAfp(Request $request, string $id)
    {
        $comision = PlanillaComisionAfp::find($id);

        if (!$comision) {
            return response()->json(['message' => 'Comisión AFP no encontrada'], 404);
        }

        $validated = $request->validate([
            'comision_flujo' => 'required|numeric|between:0,1',
            'comision_saldo' => 'required|numeric|between:0,1',
        ]);

        $comision->update($validated);

        return response()->json(['message' => 'Comisión AFP actualizada correctamente']);
    }

    /**
     * Catálogos para el alta de empleados desde Remuneraciones.
     */
    public function getCatalogosEmpleados()
    {
        return response()->json([
            'cargos' => HRPosition::orderBy('nombre')->get(['id', 'nombre']),
            'direcciones' => HrDirection::orderBy('nombre')->get(['id', 'nombre']),
            'oficinas' => HrOffice::orderBy('nombre')->get(['id', 'nombre']),
            'tipos_contrato' => HRContractType::orderBy('nombre')->get(['id', 'nombre']),
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
            ->filter(fn (Employee $empleado) => $generador->remuneracionVigente($empleado, $cierre) > 0
                && $generador->diasPagados($empleado, $periodo) > 0)
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

    // ========== NOTAS DE EMPLEADO (persisten entre planillas) ==========

    public function getNotas(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|exists:employees,id',
        ]);

        $notas = EmployeeNote::with('autor')
            ->where('employee_id', $validated['employee_id'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (EmployeeNote $nota) => [
                'id' => $nota->id,
                'texto' => $nota->texto,
                'autor' => $nota->autor?->full_name,
                'fecha' => $nota->created_at->toDateString(),
                'hora' => $nota->created_at->format('H:i'),
                'editado' => !$nota->created_at->eq($nota->updated_at),
            ]);

        return response()->json(['notas' => $notas]);
    }

    public function storeNota(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|exists:employees,id',
            'texto' => 'required|string|max:1000',
        ]);

        $nota = EmployeeNote::create([
            'employee_id' => $validated['employee_id'],
            'texto' => trim($validated['texto']),
            'registrado_por' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Nota registrada correctamente',
            'nota' => $nota,
        ], 201);
    }

    public function updateNota(Request $request, string $id)
    {
        $nota = EmployeeNote::find($id);

        if (!$nota) {
            return response()->json(['message' => 'Nota no encontrada'], 404);
        }

        $validated = $request->validate([
            'texto' => 'required|string|max:1000',
        ]);

        $nota->update(['texto' => trim($validated['texto'])]);

        return response()->json([
            'message' => 'Nota actualizada correctamente',
            'nota' => $nota->fresh(),
        ]);
    }

    public function deleteNota(string $id)
    {
        $nota = EmployeeNote::find($id);

        if (!$nota) {
            return response()->json(['message' => 'Nota no encontrada'], 404);
        }

        $nota->delete();

        return response()->json(['message' => 'Nota eliminada correctamente']);
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
                'dias_pagados' => (int) ($d->dias_pagados ?? 30),
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
                    'clasificador_id' => $item->clasificador_id,
                    'clasificador_gasto_codigo' => $item->clasificador_gasto_codigo,
                    'anio_fiscal' => $item->anio_fiscal !== null ? (int) $item->anio_fiscal : null,
                    'estado_clasificacion' => $item->estado_clasificacion,
                ])->values(),
            ]);

        $notasCounts = $detalles->isEmpty()
            ? collect()
            : EmployeeNote::whereIn('employee_id', $detalles->pluck('employee_id'))
                ->selectRaw('employee_id, COUNT(*) as total')
                ->groupBy('employee_id')
                ->pluck('total', 'employee_id');

        $asignacionesCounts = $detalles->isEmpty()
            ? collect()
            : PlanillaConceptoAsignacion::whereIn('employee_id', $detalles->pluck('employee_id'))
                ->selectRaw('employee_id, COUNT(*) as total')
                ->groupBy('employee_id')
                ->pluck('total', 'employee_id');

        $detalles = $detalles->map(fn ($d) => $d + [
            'notas_count' => (int) ($notasCounts[$d['employee_id']] ?? 0),
            'asignaciones_count' => (int) ($asignacionesCounts[$d['employee_id']] ?? 0),
        ]);

        $clasificacion = DB::table('planilla_detalle_items as i')
            ->join('planilla_detalles as d', 'd.id', 'i.detalle_id')
            ->where('d.periodo_id', $periodo->id)
            ->selectRaw("COALESCE(i.estado_clasificacion, 'PENDIENTE') AS estado")
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('ROUND(SUM(i.monto), 2) AS monto')
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(fn ($fila) => [strtolower($fila->estado) => [
                'total' => (int) $fila->total,
                'monto' => (float) $fila->monto,
            ]]);

        $clasificacionResumen = [
            'validado' => $clasificacion['validado'] ?? ['total' => 0, 'monto' => 0.0],
            'pendiente' => $clasificacion['pendiente'] ?? ['total' => 0, 'monto' => 0.0],
            'observado' => $clasificacion['observado'] ?? ['total' => 0, 'monto' => 0.0],
        ];
        $clasificacionResumen['total'] = array_sum(array_column($clasificacionResumen, 'total'));

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
                'clasificacion_gasto' => $clasificacionResumen,
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

    public function getResumen(Request $request)
    {
        $periodo = $this->periodoParaResumen($request);

        if (!$periodo) {
            return response()->json(['message' => 'No hay periodos registrados'], 404);
        }

        $codigos = PlanillaConcepto::pluck('codigo', 'id');
        $nombres = PlanillaConcepto::pluck('nombre', 'id');

        $ingresos = ['i' => 0.0, 't' => 0.0];
        $aguinaldo = 0.0;
        $essalud = 0.0;
        $afp = [
            'HABITAT' => ['i' => 0.0, 't' => 0.0],
            'INTEGRA' => ['i' => 0.0, 't' => 0.0],
            'PRIMA' => ['i' => 0.0, 't' => 0.0],
            'PROFUTURO' => ['i' => 0.0, 't' => 0.0],
        ];
        $afpOtra = ['i' => 0.0, 't' => 0.0];
        $onp = ['i' => 0.0, 't' => 0.0];
        $faltas = ['i' => 0.0, 't' => 0.0];
        $subcafae = ['i' => 0.0, 't' => 0.0];
        $otros = [];
        $neto = ['i' => 0.0, 't' => 0.0];
        $empleados = 0;

        $detalles = $periodo->detalles()
            ->with(['employee.payrollProfile.regimenPensionario', 'items'])
            ->get();

        foreach ($detalles as $detalle) {
            $empleados++;
            $columna = $detalle->employee?->modalidadCas() === 'TRANSITORIO' ? 't' : 'i';
            $neto[$columna] += (float) $detalle->neto_pagar;

            $administradora = strtoupper($detalle->employee?->payrollProfile?->regimenPensionario?->nombre ?? '');

            foreach ($detalle->items as $item) {
                $codigo = $codigos[$item->concepto_id] ?? null;
                $monto = (float) $item->monto;

                if ($codigo === 'AGUINALDO' || $codigo === 'GRATIFICACION') {
                    $aguinaldo += $monto;
                    continue;
                }

                if ($codigo === 'ESSALUD') {
                    $essalud += $monto;
                    continue;
                }

                if ($item->tipo === 'INGRESO') {
                    $ingresos[$columna] += $monto;
                    continue;
                }

                if (in_array($codigo, ['AFP_FONDO', 'AFP_SEGURO', 'AFP_COMISION'], true)) {
                    $clave = null;
                    foreach (array_keys($afp) as $nombre) {
                        if (str_contains($administradora, $nombre)) {
                            $clave = $nombre;
                            break;
                        }
                    }

                    if ($clave) {
                        $afp[$clave][$columna] += $monto;
                    } else {
                        $afpOtra[$columna] += $monto;
                    }
                    continue;
                }

                if ($codigo === 'ONP_19990') {
                    $onp[$columna] += $monto;
                    continue;
                }

                if ($codigo === 'FALTAS_TARDANZAS') {
                    $faltas[$columna] += $monto;
                    continue;
                }

                if ($codigo === 'SUBCAFAE') {
                    $subcafae[$columna] += $monto;
                    continue;
                }

                if ($item->tipo === 'DESCUENTO' && $monto != 0.0) {
                    $claveOtros = $codigo ?? 'SIN_CODIGO';
                    if (!isset($otros[$claveOtros])) {
                        $otros[$claveOtros] = [
                            'nombre' => $nombres[$item->concepto_id] ?? 'Otro descuento',
                            'i' => 0.0,
                            't' => 0.0,
                        ];
                    }
                    $otros[$claveOtros][$columna] += $monto;
                }
            }
        }

        $fila = fn (string $nombre, array $v): array => [
            'nombre' => $nombre,
            'c13_11' => round($v['i'], 2),
            'c13_12' => round($v['t'], 2),
            'total' => round($v['i'] + $v['t'], 2),
        ];

        $descuentos = [];
        foreach ($afp as $nombre => $v) {
            $descuentos[] = $fila('AFP '.$nombre, $v);
        }
        if ($afpOtra['i'] != 0.0 || $afpOtra['t'] != 0.0) {
            $descuentos[] = $fila('AFP (otra)', $afpOtra);
        }
        $descuentos[] = $fila('ONP DL. 19990', $onp);
        $descuentos[] = $fila('Dsctos/Tardanzas', $faltas);
        $descuentos[] = $fila('Lic. Cta. Essalud', ['i' => 0.0, 't' => 0.0]);
        $descuentos[] = $fila('Pagos Indebidos', ['i' => 0.0, 't' => 0.0]);
        $descuentos[] = $fila('Sub CAFAE', $subcafae);
        foreach ($otros as $v) {
            if ($v['i'] != 0.0 || $v['t'] != 0.0) {
                $descuentos[] = $fila($v['nombre'], $v);
            }
        }

        $totalPlanillas = round(array_sum($ingresos) + $aguinaldo + $essalud, 2);
        $totalDescuentos = round(array_sum(array_column($descuentos, 'total')), 2);
        $totalLiquido = round($totalPlanillas - $totalDescuentos - $essalud, 2);

        return response()->json([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'anio' => $periodo->anio,
                'mes' => $periodo->mes,
                'estado' => $periodo->estado,
                'total_empleados' => $periodo->total_empleados,
                'total_neto' => (float) $periodo->total_neto,
            ],
            'empleados' => $empleados,
            'ingresos' => [
                ['esp_gasto' => '2.1.1 13.11', 'nombre' => 'CAS Indeterminado (DL 1057, bonos DS, reintegros)', 'monto' => round($ingresos['i'], 2)],
                ['esp_gasto' => '2.1.1 13.12', 'nombre' => 'CAS Transitorio (DL 1057, bonos DS, reintegros)', 'monto' => round($ingresos['t'], 2)],
                ['esp_gasto' => '2.1.1 9.14', 'nombre' => 'Aguinaldo / Gratificación', 'monto' => round($aguinaldo, 2)],
                ['esp_gasto' => '2.1.31.1 15', 'nombre' => 'Aporte Essalud (empleador)', 'monto' => round($essalud, 2)],
            ],
            'total_planillas' => $totalPlanillas,
            'descuentos' => $descuentos,
            'total_descuentos' => $totalDescuentos,
            'abono' => [
                'c13_11' => round($neto['i'], 2),
                'c13_12' => round($neto['t'], 2),
                'teleahorro' => round($neto['i'] + $neto['t'], 2),
                'aporte_essalud' => round($essalud, 2),
                'total_liquido' => $totalLiquido,
            ],
        ]);
    }

    private function periodoParaResumen(Request $request): ?PlanillaPeriodo
    {
        $datos = $request->validate([
            'periodo_id' => 'nullable|string|max:36',
        ]);

        return !empty($datos['periodo_id'])
            ? PlanillaPeriodo::find($datos['periodo_id'])
            : PlanillaPeriodo::orderByDesc('anio')->orderByDesc('mes')->first();
    }

    /**
     * Datos del «RESUMEN PLANILLA CAS» (bloques 2.1.1 13.11 / 2.1.1 13.12
     * de la hoja Planilla), agrupados por modalidad CAS.
     *
     * @return array{bloques: array<string, array>, totales: array<string, float>}
     */
    private function resumenPlanillaData(PlanillaPeriodo $periodo): array
    {
        $genericas = [
            '13.11' => ['modalidad' => Employee::MODALIDAD_INDETERMINADO, 'esp_gasto' => '2.1.1 13.11'],
            '13.12' => ['modalidad' => Employee::MODALIDAD_TRANSITORIO, 'esp_gasto' => '2.1.1 13.12'],
        ];

        $etiquetasIngresos = [
            'REM_DL1057' => 'D.L. 1057',
            'DS311_2022' => 'DS 311-2022-EF',
            'DS313_2023' => 'DS 313-2023',
            'DS265_279_2024' => 'DS 265 y 279-2024',
            'DS327_2025' => 'DS 327-2025',
            'AGUINALDO' => 'Aguinaldo',
            'REINTEGRO' => 'Reintegro',
        ];
        $ordenIngresos = array_keys($etiquetasIngresos);

        // Los códigos de catálogo DS 265 / DS 279 (separados o combinados)
        // se reportan en una sola fila «DS 265 y 279-2024».
        $aliasIngresos = [
            'DS_265_2024' => 'DS265_279_2024',
            'DS_279_2024' => 'DS265_279_2024',
        ];

        $etiquetasDescuentos = [
            'AFP:HABITAT' => 'AFP Habitat',
            'AFP:INTEGRA' => 'AFP Integra',
            'AFP:PRIMA' => 'AFP Prima',
            'AFP:PROFUTURO' => 'AFP Profuturo',
            'ONP_19990' => 'LEY 19990 (ONP)',
            'FALTAS_TARDANZAS' => 'Dsctos/Tardanzas',
            'RENTA_4TA' => 'Rta. 4ta. Cat.',
            'PAGO_INDEBIDO' => 'Pagos Indebidos',
            'LIC_ESSALUD' => 'Lic. Cta. Essalud',
            'SUBCAFAE' => 'Sub CAFAE',
        ];
        $administradoras = ['HABITAT', 'INTEGRA', 'PRIMA', 'PROFUTURO'];

        $codigos = PlanillaConcepto::pluck('codigo', 'id');
        $nombresPorCodigo = PlanillaConcepto::pluck('nombre', 'codigo');

        $acumulados = [];
        foreach (array_keys($genericas) as $clave) {
            $acumulados[$clave] = ['ingresos' => [], 'descuentos' => [], 'essalud' => 0.0, 'neto' => 0.0];
        }

        foreach ($periodo->detalles()->with(['employee.payrollProfile.regimenPensionario', 'items'])->get() as $detalle) {
            $bloque = $detalle->employee?->modalidadCas() === Employee::MODALIDAD_TRANSITORIO ? '13.12' : '13.11';
            $acumulados[$bloque]['neto'] += (float) $detalle->neto_pagar;

            $administradora = strtoupper($detalle->employee?->payrollProfile?->regimenPensionario?->nombre ?? '');

            foreach ($detalle->items as $item) {
                $codigo = $codigos[$item->concepto_id] ?? null;
                $monto = (float) $item->monto;

                if ($codigo === 'ESSALUD') {
                    $acumulados[$bloque]['essalud'] += $monto;
                    continue;
                }

                if ($item->tipo === 'INGRESO') {
                    $codigo = $aliasIngresos[$codigo] ?? $codigo;
                    $llave = in_array($codigo, $ordenIngresos, true) ? $codigo : 'X:'.$codigo;
                    $acumulados[$bloque]['ingresos'][$llave] = ($acumulados[$bloque]['ingresos'][$llave] ?? 0.0) + $monto;
                    continue;
                }

                if (in_array($codigo, ['AFP_FONDO', 'AFP_SEGURO', 'AFP_COMISION'], true)) {
                    $llave = 'AFP:OTRA';
                    foreach ($administradoras as $nombre) {
                        if (str_contains($administradora, $nombre)) {
                            $llave = 'AFP:'.$nombre;
                            break;
                        }
                    }
                    $acumulados[$bloque]['descuentos'][$llave] = ($acumulados[$bloque]['descuentos'][$llave] ?? 0.0) + $monto;
                    continue;
                }

                if ($item->tipo === 'DESCUENTO' && $monto != 0.0) {
                    $llave = isset($etiquetasDescuentos[$codigo]) ? $codigo : 'X:'.$codigo;
                    $acumulados[$bloque]['descuentos'][$llave] = ($acumulados[$bloque]['descuentos'][$llave] ?? 0.0) + $monto;
                }
            }
        }

        $bloques = [];
        foreach ($genericas as $clave => $config) {
            $acum = $acumulados[$clave];

            $filasIngresos = [];
            foreach ($ordenIngresos as $codigo) {
                $filasIngresos[] = [
                    'esp_gasto' => $codigo === 'AGUINALDO' ? '2.1.1 9.14' : $config['esp_gasto'],
                    'nombre' => $etiquetasIngresos[$codigo],
                    'monto' => round($acum['ingresos'][$codigo] ?? 0.0, 2),
                ];
            }
            foreach ($acum['ingresos'] as $llave => $monto) {
                if (str_starts_with($llave, 'X:')) {
                    $codigo = substr($llave, 2);
                    $filasIngresos[] = [
                        'esp_gasto' => $config['esp_gasto'],
                        'nombre' => $nombresPorCodigo[$codigo] ?? 'Otro ingreso',
                        'monto' => round($monto, 2),
                    ];
                }
            }

            $filasDescuentos = [];
            foreach ($etiquetasDescuentos as $llave => $nombre) {
                $filasDescuentos[] = [
                    'nombre' => $nombre,
                    'monto' => round($acum['descuentos'][$llave] ?? 0.0, 2),
                ];
            }
            foreach ($acum['descuentos'] as $llave => $monto) {
                if (str_starts_with($llave, 'X:')) {
                    $codigo = substr($llave, 2);
                    $filasDescuentos[] = [
                        'nombre' => $nombresPorCodigo[$codigo] ?? 'Otro descuento',
                        'monto' => round($monto, 2),
                    ];
                } elseif ($llave === 'AFP:OTRA') {
                    $filasDescuentos[] = ['nombre' => 'AFP (otra)', 'monto' => round($monto, 2)];
                }
            }

            $bloques[$clave] = [
                'generica' => $config['esp_gasto'],
                'ingresos' => $filasIngresos,
                'essalud' => round($acum['essalud'], 2),
                'total_ingresos' => round(array_sum(array_column($filasIngresos, 'monto')), 2),
                'descuentos' => $filasDescuentos,
                'total_descuentos' => round(array_sum(array_column($filasDescuentos, 'monto')), 2),
                'neto' => round($acum['neto'], 2),
            ];
        }

        $aporte = round($bloques['13.11']['essalud'] + $bloques['13.12']['essalud'], 2);

        return [
            'bloques' => $bloques,
            'totales' => [
                'liquido' => round($bloques['13.11']['neto'] + $bloques['13.12']['neto'], 2),
                'descuento' => round($bloques['13.11']['total_descuentos'] + $bloques['13.12']['total_descuentos'], 2),
                'aporte' => $aporte,
                'planilla' => round($bloques['13.11']['total_ingresos'] + $bloques['13.12']['total_ingresos'] + $aporte, 2),
            ],
        ];
    }

    public function getResumenPlanilla(Request $request)
    {
        $periodo = $this->periodoParaResumen($request);

        if (!$periodo) {
            return response()->json(['message' => 'No hay periodos registrados'], 404);
        }

        return response()->json(array_merge([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'anio' => $periodo->anio,
                'mes' => $periodo->mes,
                'estado' => $periodo->estado,
                'total_empleados' => $periodo->total_empleados,
                'total_neto' => (float) $periodo->total_neto,
            ],
        ], $this->resumenPlanillaData($periodo)));
    }

    public function exportResumenPlanilla(Request $request, string $format)
    {
        if (!in_array($format, ['xlsx', 'pdf'], true)) {
            return response()->json(['message' => 'Formato no soportado'], 422);
        }

        $periodo = $this->periodoParaResumen($request);

        if (!$periodo) {
            return response()->json(['message' => 'No hay periodos registrados'], 404);
        }

        $datos = $this->resumenPlanillaData($periodo);

        return $format === 'xlsx'
            ? $this->descargarResumenPlanillaXlsx($periodo, $datos)
            : $this->descargarResumenPlanillaPdf($periodo, $datos);
    }

    private function descargarResumenPlanillaXlsx(PlanillaPeriodo $periodo, array $datos)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen Planilla CAS');
        $sheet->getDefaultColumnDimension()->setWidth(14);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(34);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(3);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(16);

        $tituloStyle = ['font' => ['bold' => true, 'size' => 14]];
        $seccionStyle = ['font' => ['bold' => true, 'size' => 11]];
        $filaStyle = [
            'font' => ['size' => 10],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E0']]],
        ];
        $filaTitularStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'borders' => $filaStyle['borders'],
        ];
        $cabeceraStyle = [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $moneda = '#,##0.00';

        $fila = 1;
        $sheet->mergeCells("A{$fila}:F{$fila}");
        $sheet->setCellValue("A{$fila}", 'RESUMEN PLANILLA CAS')->getStyle("A{$fila}")->applyFromArray($tituloStyle);
        $fila++;
        $sheet->mergeCells("A{$fila}:F{$fila}");
        $sheet->setCellValue("A{$fila}", $periodo->nombre_periodo);
        $sheet->getStyle("A{$fila}")->applyFromArray(['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => 'center']]);
        $fila += 2;

        foreach ($datos['bloques'] as $clave => $bloque) {
            $sheet->mergeCells("A{$fila}:C{$fila}");
            $sheet->setCellValue("A{$fila}", 'Génerica de gasto '.$bloque['generica'])->getStyle("A{$fila}")->applyFromArray($seccionStyle);
            $sheet->setCellValue("E{$fila}", 'Essalud');
            $sheet->setCellValue("F{$fila}", $bloque['essalud']);
            $sheet->getStyle("F{$fila}")->getNumberFormat()->setFormatCode($moneda);
            $fila++;

            $sheet->setCellValue("A{$fila}", 'Ingresos')->getStyle("A{$fila}")->applyFromArray($seccionStyle);
            $fila++;

            foreach ([['Esp. Gasto', 'A'], ['Concepto', 'B'], ['Monto', 'C']] as [$titulo, $columna]) {
                $sheet->setCellValue("{$columna}{$fila}", $titulo);
            }
            $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($cabeceraStyle);
            $fila++;

            foreach ($bloque['ingresos'] as $ingreso) {
                $sheet->setCellValue("A{$fila}", $ingreso['esp_gasto']);
                $sheet->setCellValue("B{$fila}", $ingreso['nombre']);
                $sheet->setCellValue("C{$fila}", $ingreso['monto']);
                $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($filaStyle);
                $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode($moneda);
                $fila++;
            }

            $sheet->setCellValue("B{$fila}", 'Total Ingresos');
            $sheet->setCellValue("C{$fila}", $bloque['total_ingresos']);
            $sheet->getStyle("B{$fila}:C{$fila}")->applyFromArray($filaTitularStyle);
            $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode($moneda);
            $fila += 2;

            $sheet->setCellValue("A{$fila}", 'Descuentos')->getStyle("A{$fila}")->applyFromArray($seccionStyle);
            $fila++;

            $sheet->setCellValue("A{$fila}", 'Concepto');
            $sheet->setCellValue("C{$fila}", 'Monto');
            $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($cabeceraStyle);
            $fila++;

            foreach ($bloque['descuentos'] as $descuento) {
                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", $descuento['nombre']);
                $sheet->setCellValue("C{$fila}", $descuento['monto']);
                $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($filaStyle);
                $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode($moneda);
                $fila++;
            }

            $sheet->mergeCells("A{$fila}:B{$fila}");
            $sheet->setCellValue("A{$fila}", 'Total Descuentos');
            $sheet->setCellValue("C{$fila}", $bloque['total_descuentos']);
            $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($filaTitularStyle);
            $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode($moneda);
            $fila += 2;

            $sheet->mergeCells("A{$fila}:B{$fila}");
            $sheet->setCellValue("A{$fila}", 'Neto a Pagar');
            $sheet->setCellValue("C{$fila}", $bloque['neto']);
            $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($filaTitularStyle);
            $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode($moneda);
            $fila += 3;
        }

        $totales = [
            ['Total Líquido', $datos['totales']['liquido']],
            ['Total Descuento', $datos['totales']['descuento']],
            ['Total Aporte (Essalud)', $datos['totales']['aporte']],
            ['Total Planilla', $datos['totales']['planilla']],
        ];
        foreach ($totales as [$etiqueta, $monto]) {
            $sheet->mergeCells("A{$fila}:B{$fila}");
            $sheet->setCellValue("A{$fila}", $etiqueta);
            $sheet->setCellValue("C{$fila}", $monto);
            $sheet->getStyle("A{$fila}:C{$fila}")->applyFromArray($filaTitularStyle);
            $sheet->getStyle("C{$fila}")->getNumberFormat()->setFormatCode($moneda);
            $fila++;
        }

        $nombre = sprintf('resumen_planilla_%d_%02d.xlsx', $periodo->anio, $periodo->mes);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function descargarResumenPlanillaPdf(PlanillaPeriodo $periodo, array $datos)
    {
        $pdf = Pdf::loadView('pdf.resumen_planilla', [
            'periodo' => $periodo,
            'bloques' => $datos['bloques'],
            'totales' => $datos['totales'],
        ]);

        return $pdf->stream(sprintf('resumen_planilla_%d_%02d.pdf', $periodo->anio, $periodo->mes));
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
            'boletas_emitidas' => PlanillaDetalle::whereHas(
                'periodo',
                fn ($query) => $query->where('estado', '!=', 'BORRADOR')
            )->count(),
            'personal_en_planilla' => $personal,
            'pendientes' => PlanillaPeriodo::where('estado', 'BORRADOR')->count(),
        ]);
    }
}
