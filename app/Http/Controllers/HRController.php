<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vacation;
use App\Models\HRArea;
use App\Models\HRPosition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Person;
use App\Models\HrOffice;
use App\Models\HRContractType;
use Carbon\Carbon;

class HRController extends Controller
{
    /**
     * Display the HR management page
     */
    public function index()
    {
        return Inertia::render('HR/Index');
    }

    /**
     * Get all employees
     */
    public function getEmployees()
    {
        // Cargamos la relación person para tener acceso a nombres/apellidos
        // Ordenamos la colección resultante usando el accessor 'apellidos'
        $employees = Employee::with(['person', 'area', 'position'])
            ->get()
            ->sortBy('apellidos', SORT_NATURAL | SORT_FLAG_CASE)
            ->values(); // Reindexar array
        
        return response()->json($employees);
    }

    /**
     * Get a single employee by ID
     */
    public function getEmployee(string $id)
    {
        $employee = Employee::with(['person', 'vacations', 'area', 'position'])->find($id);
        
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }
        
        return response()->json($employee);
    }

    /**
     * Get employee by DNI
     */
    public function getEmployeeByDni(string $dni)
    {
        // Buscar empleado que tenga una persona con ese DNI
        $employee = Employee::whereHas('person', function($query) use ($dni) {
            $query->where('dni', $dni);
        })->with(['person', 'area', 'position'])->first();
        
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }
        
        return response()->json($employee);
    }



    /**
     * Create a new employee
     * Normalizado para usar tabla people
     */
    public function storeEmployee(Request $request)
    {
        // Validación inicial
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:M,F,Masculino,Femenino',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            // Datos laborales
            'cargo_id' => 'nullable|exists:hr_positions,id', // Idealmente usar IDs
            'area_id' => 'nullable|exists:hr_areas,id',     // Idealmente usar IDs
            'fecha_ingreso' => 'nullable|date',
            'tipo_contrato' => 'nullable|string', // Ahora puede ser ID o nombre
            'contract_type_id' => 'nullable|exists:hr_contract_types,id',
            'observaciones' => 'nullable|string',
        ]);

        // 1. Buscar o Crear Persona
        $person = Person::firstOrNew(['dni' => $validated['dni']]);
        
        // Actualizar datos personales
        $person->nombres = $validated['nombres'];
        $person->apellidos = $validated['apellidos'];
        $person->fecha_nacimiento = $validated['fecha_nacimiento'] ?? $person->fecha_nacimiento;
        $person->genero = $validated['genero'] === 'Masculino' ? 'M' : ($validated['genero'] === 'Femenino' ? 'F' : ($validated['genero'] ?? $person->genero));
        $person->direccion = $validated['direccion'] ?? $person->direccion;
        $person->telefono = $validated['telefono'] ?? $person->telefono;
        $person->email = $validated['correo'] ?? $person->email;
        $person->tipo = 'INTERNO'; // Promover a interno
        $person->is_active = true;
        $person->save();

        // 2. Verificar si ya es empleado
        if ($person->employee) {
            return response()->json(['message' => 'Esta persona ya está registrada como empleado.'], 422);
        }

        // 3. Crear Empleado
        // Mapeo temporal si el frontend envía nombres en lugar de IDs (para compatibilidad)
        $areaId = $request->area_id;
        if (!$areaId && $request->area) {
             $areaObj = HRArea::where('nombre', $request->area)->first();
             $areaId = $areaObj?->id;
        }

        $positionId = $request->cargo_id;
        if (!$positionId && $request->cargo) {
             $posObj = HRPosition::where('nombre', $request->cargo)->first();
             $positionId = $posObj?->id;
        }

        // Mapeo de Tipo de Contrato
        $contractTypeId = $request->contract_type_id;
        if (!$contractTypeId && $request->tipo_contrato) {
             // Buscar por nombre exacto primero
             $ctObj = HRContractType::where('nombre', $request->tipo_contrato)->first();
             if ($ctObj) {
                 $contractTypeId = $ctObj->id;
             } else {
                 // Opción: Crear si no existe o usar default. 
                 // Por ahora, creemos uno nuevo si no existe para mantener compatibilidad total
                 $ctObj = HRContractType::create([
                     'nombre' => $request->tipo_contrato,
                     'activo' => true
                 ]);
                 $contractTypeId = $ctObj->id;
             }
        }

        $employee = Employee::create([
            'person_id' => $person->id,
            'area_id' => $areaId,
            'position_id' => $positionId,
            'contract_type_id' => $contractTypeId,
            'fecha_ingreso' => $validated['fecha_ingreso'] ?? now(),
            // 'tipo_contrato' ya no se usa directamente
            'observaciones' => $validated['observaciones'],
            'estado' => 'ACTIVO',
        ]);
        
        return response()->json([
            'message' => 'Empleado registrado correctamente',
            'id' => $employee->id
        ], 201);
    }

    /**
     * Update an employee
     */
    public function updateEmployee(Request $request, string $id)
    {
        $employee = Employee::with('person')->find($id);
        
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        // Validación
        $validated = $request->validate([
            'dni' => 'sometimes|string|size:8', // Validar unicidad en people si cambia, pero es complejo aqui
            'nombres' => 'sometimes|string|max:255',
            'apellidos' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
            // Laborales
            'cargo_id' => 'nullable|exists:hr_positions,id',
            'area_id' => 'nullable|exists:hr_areas,id',
            'fecha_ingreso' => 'nullable|date',
            'fecha_ingreso' => 'nullable|date',
            'tipo_contrato' => 'nullable|string',
            'contract_type_id' => 'nullable|exists:hr_contract_types,id',
            'estado' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        // 1. Actualizar Persona
        if ($employee->person) {
            $personData = [];
            if ($request->has('nombres')) $personData['nombres'] = $validated['nombres'];
            if ($request->has('apellidos')) $personData['apellidos'] = $validated['apellidos'];
            if ($request->has('telefono')) $personData['telefono'] = $validated['telefono'];
            if ($request->has('correo')) $personData['email'] = $validated['correo'];
            if ($request->has('direccion')) $personData['direccion'] = $validated['direccion'];
            
            // Si cambia DNI (caso raro)
            if ($request->has('dni') && $validated['dni'] !== $employee->person->dni) {
                // Verificar que no exista otro
                if (Person::where('dni', $validated['dni'])->where('id', '!=', $employee->person_id)->exists()) {
                     return response()->json(['message' => 'El DNI ya pertenece a otra persona.'], 422);
                }
                $personData['dni'] = $validated['dni'];
            }

            if (!empty($personData)) {
                $employee->person->update($personData);
            }
        }

        // 2. Actualizar Empleado (Datos laborales)
        $employeeData = [];
        if ($request->has('fecha_ingreso')) $employeeData['fecha_ingreso'] = $validated['fecha_ingreso'];
        if ($request->has('estado')) $employeeData['estado'] = $validated['estado'];
        if ($request->has('observaciones')) $employeeData['observaciones'] = $validated['observaciones'];

        // Mapeo de Tipo de Contrato
        if ($request->has('contract_type_id')) {
            $employeeData['contract_type_id'] = $validated['contract_type_id'];
        } elseif ($request->has('tipo_contrato')) {
             $typeName = $validated['tipo_contrato'];
             $ctObj = HRContractType::where('nombre', $typeName)->first();
             if ($ctObj) {
                 $employeeData['contract_type_id'] = $ctObj->id;
             } else {
                 // Auto-crear para compatibilidad
                 $ctObj = HRContractType::create(['nombre' => $typeName, 'activo' => true]);
                 $employeeData['contract_type_id'] = $ctObj->id;
             }
        }
        
        // Mapeo de IDs
        if ($request->has('area_id')) $employeeData['area_id'] = $validated['area_id'];
        elseif ($request->has('area')) { // fallback nombre
             $areaObj = HRArea::where('nombre', $request->area)->first();
             if ($areaObj) $employeeData['area_id'] = $areaObj->id;
        }

        if ($request->has('cargo_id')) $employeeData['position_id'] = $validated['cargo_id'];
        elseif ($request->has('cargo')) { // fallback nombre
             $posObj = HRPosition::where('nombre', $request->cargo)->first();
             if ($posObj) $employeeData['position_id'] = $posObj->id;
        }

        if (!empty($employeeData)) {
            $employee->update($employeeData);
        }
        
        return response()->json(['message' => 'Empleado actualizado correctamente']);
    }

    // ========== VACATION METHODS ==========

    /**
     * Get all vacations
     */
    public function getVacations()
    {
        $vacations = Vacation::with('employee')
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->map(function ($vacation) {
                return [
                    'id' => $vacation->id,
                    'empleado_id' => $vacation->empleado_id,
                    'dni' => $vacation->dni,
                    'periodo' => $vacation->periodo,
                    'fecha_inicio' => $vacation->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $vacation->fecha_fin?->format('Y-m-d'),
                    'dias_tomados' => $vacation->dias_tomados,
                    'dias_pendientes' => $vacation->dias_pendientes,
                    'estado' => $vacation->estado,
                    'observaciones' => $vacation->observaciones,
                    'empleado' => $vacation->employee ? [
                        'nombres' => $vacation->employee->nombres,
                        'apellidos' => $vacation->employee->apellidos,
                    ] : null,
                ];
            });
        
        return response()->json($vacations);
    }

    /**
     * Create a vacation record
     */
    public function storeVacation(Request $request)
    {
        $validated = $request->validate([
            'empleado_id' => 'required|exists:employees,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string',
        ]);

        $employee = Employee::find($validated['empleado_id']);

        // Calculate days
        $fechaInicio = Carbon::parse($validated['fecha_inicio']);
        $fechaFin = Carbon::parse($validated['fecha_fin']);
        $diasTomados = $fechaInicio->diffInDays($fechaFin) + 1;

        $vacation = Vacation::create([
            'empleado_id' => $validated['empleado_id'],
            'dni' => $employee->dni,
            'periodo' => now()->year,
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'dias_tomados' => $diasTomados,
            'dias_pendientes' => max(0, 30 - $diasTomados),
            'estado' => 'PROGRAMADO',
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return response()->json([
            'message' => 'Vacaciones registradas correctamente',
            'id' => $vacation->id
        ], 201);
    }

    /**
     * Update a vacation record
     */
    public function updateVacation(Request $request, string $id)
    {
        $vacation = Vacation::find($id);

        if (!$vacation) {
            return response()->json(['message' => 'Vacación no encontrada'], 404);
        }

        $validated = $request->validate([
            'empleado_id' => 'sometimes|exists:employees,id',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
            'observaciones' => 'nullable|string',
        ]);

        // If employee changed, update DNI
        if (isset($validated['empleado_id'])) {
            $employee = Employee::find($validated['empleado_id']);
            $validated['dni'] = $employee->dni;
        }

        // Recalculate days if dates changed
        if (isset($validated['fecha_inicio']) || isset($validated['fecha_fin'])) {
            $fechaInicio = Carbon::parse($validated['fecha_inicio'] ?? $vacation->fecha_inicio);
            $fechaFin = Carbon::parse($validated['fecha_fin'] ?? $vacation->fecha_fin);
            $validated['dias_tomados'] = $fechaInicio->diffInDays($fechaFin) + 1;
            $validated['dias_pendientes'] = max(0, 30 - $validated['dias_tomados']);
        }

        $vacation->update($validated);

        return response()->json(['message' => 'Vacaciones actualizadas correctamente']);
    }

    /**
     * Delete a vacation record
     */
    public function deleteVacation(string $id)
    {
        $vacation = Vacation::find($id);

        if (!$vacation) {
            return response()->json(['message' => 'Vacación no encontrada'], 404);
        }

        $vacation->delete();

        return response()->json(['message' => 'Vacación eliminada correctamente']);
    }

    // ========== SUMMARY ==========

    /**
     * Get HR summary statistics
     */
    public function getSummary()
    {
        $employees = Employee::all();
        $vacations = Vacation::all();
        
        $today = now()->toDateString();
        
        // Empleados de vacaciones actualmente
        $onVacation = Vacation::where('fecha_inicio', '<=', $today)
            ->where('fecha_fin', '>=', $today)
            ->where('estado', '!=', 'CANCELADO')
            ->count();
        
        // Conteo por área
        $byArea = $employees->groupBy('area')->map->count();
        
        // Conteo por tipo de contrato
        $byContract = $employees->groupBy('tipo_contrato')->map->count();
        
        return response()->json([
            'total_empleados' => $employees->count(),
            'empleados_activos' => $employees->where('estado', 'ACTIVO')->count(),
            'empleados_inactivos' => $employees->where('estado', '!=', 'ACTIVO')->count(),
            'en_vacaciones' => $onVacation,
            'vacaciones_programadas' => $vacations->where('estado', 'PROGRAMADO')->count(),
            'por_area' => $byArea,
            'por_tipo_contrato' => $byContract,
        ]);
    }
    // ========== AREA METHODS ==========

    /**
     * Get all areas
     */
    public function getAreas()
    {
        $areas = HRArea::orderBy('nombre')->get();
        return response()->json($areas);
    }

    /**
     * Create a new area
     */
    public function storeArea(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:hr_areas,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.unique' => 'Ya existe un área con ese nombre',
        ]);

        $area = HRArea::create($validated);
        
        return response()->json([
            'message' => 'Área registrada correctamente',
            'area' => $area
        ], 201);
    }

    /**
     * Update an area
     */
    public function updateArea(Request $request, string $id)
    {
        $area = HRArea::find($id);
        
        if (!$area) {
            return response()->json(['message' => 'Área no encontrada'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:hr_areas,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $area->update($validated);
        
        return response()->json(['message' => 'Área actualizada correctamente']);
    }

    /**
     * Delete an area
     */
    public function deleteArea(string $id)
    {
        $area = HRArea::find($id);
        
        if (!$area) {
            return response()->json(['message' => 'Área no encontrada'], 404);
        }

        // Optional: check if area is being used by employees before deleting or just allow it
        $area->delete();
        
        return response()->json(['message' => 'Área eliminada correctamente']);
    }
    // ========== POSITION METHODS ==========

    /**
     * Get all positions
     */
    public function getPositions()
    {
        $positions = HRPosition::orderBy('nombre')->get();
        return response()->json($positions);
    }

    /**
     * Create a new position
     */
    public function storePosition(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:hr_positions,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.unique' => 'Ya existe un cargo con ese nombre',
        ]);

        $position = HRPosition::create($validated);
        
        return response()->json([
            'message' => 'Cargo registrado correctamente',
            'position' => $position
        ], 201);
    }

    /**
     * Update a position
     */
    public function updatePosition(Request $request, string $id)
    {
        $position = HRPosition::find($id);
        
        if (!$position) {
            return response()->json(['message' => 'Cargo no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:hr_positions,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $position->update($validated);
        
        return response()->json(['message' => 'Cargo actualizado correctamente']);
    }

    /**
     * Delete a position
     */
    public function deletePosition(string $id)
    {
        $position = HRPosition::find($id);
        
        if (!$position) {
            return response()->json(['message' => 'Cargo no encontrado'], 404);
        }

        $position->delete();
        
        return response()->json(['message' => 'Cargo eliminado correctamente']);
    }

    // ========== OFFICE METHODS ==========

    /**
     * Get all offices
     */
    public function getOffices()
    {
        $offices = HrOffice::with('area')->orderBy('nombre')->get();
        return response()->json($offices);
    }

    /**
     * Create a new office
     */
    public function storeOffice(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'required|exists:hr_areas,id',
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:20',
            'ubicacion' => 'nullable|string|max:255',
            'telefono_interno' => 'nullable|string|max:50',
            'activo' => 'boolean',
        ]);

        $office = HrOffice::create($validated);
        $office->load('area');
        
        return response()->json([
            'message' => 'Oficina registrada correctamente',
            'office' => $office
        ], 201);
    }

    /**
     * Update an office
     */
    public function updateOffice(Request $request, string $id)
    {
        $office = HrOffice::find($id);
        
        if (!$office) {
            return response()->json(['message' => 'Oficina no encontrada'], 404);
        }

        $validated = $request->validate([
            'area_id' => 'sometimes|exists:hr_areas,id',
            'nombre' => 'sometimes|string|max:255',
            'codigo' => 'nullable|string|max:20',
            'ubicacion' => 'nullable|string|max:255',
            'telefono_interno' => 'nullable|string|max:50',
            'activo' => 'boolean',
        ]);

        $office->update($validated);
        $office->load('area');
        
        return response()->json([
            'message' => 'Oficina actualizada correctamente',
            'office' => $office
        ]);
    }

    /**
     * Delete an office
     */
    public function deleteOffice(string $id)
    {
        $office = HrOffice::find($id);
        
        if (!$office) {
            return response()->json(['message' => 'Oficina no encontrada'], 404);
        }

        $office->delete();
        
        return response()->json(['message' => 'Oficina eliminada correctamente']);
    }

    // ========== CONTRACT TYPE METHODS ==========

    /**
     * Get all contract types
     */
    public function getContractTypes()
    {
        $types = HRContractType::orderBy('nombre')->get();
        return response()->json($types);
    }

    /**
     * Create a new contract type
     */
    public function storeContractType(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:hr_contract_types,nombre',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ], [
            'nombre.unique' => 'Ya existe un tipo de contrato con ese nombre',
        ]);

        $type = HRContractType::create($validated);
        
        return response()->json([
            'message' => 'Tipo de contrato registrado correctamente',
            'contract_type' => $type
        ], 201);
    }

    /**
     * Update a contract type
     */
    public function updateContractType(Request $request, string $id)
    {
        $type = HRContractType::find($id);
        
        if (!$type) {
            return response()->json(['message' => 'Tipo de contrato no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:255|unique:hr_contract_types,nombre,' . $id,
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $type->update($validated);
        
        return response()->json(['message' => 'Tipo de contrato actualizado correctamente']);
    }

    /**
     * Delete a contract type
     */
    public function deleteContractType(string $id)
    {
        $type = HRContractType::find($id);
        
        if (!$type) {
            return response()->json(['message' => 'Tipo de contrato no encontrado'], 404);
        }

        $type->delete();
        
        return response()->json(['message' => 'Tipo de contrato eliminado correctamente']);
    }
}
