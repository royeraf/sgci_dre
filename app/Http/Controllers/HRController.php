<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Vacation;
use App\Models\HRArea;
use App\Models\HRPosition;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
        $employees = Employee::orderBy('apellidos')->get();
        
        return response()->json($employees);
    }

    /**
     * Get a single employee by ID
     */
    public function getEmployee(string $id)
    {
        $employee = Employee::with('vacations')->find($id);
        
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
        $employee = Employee::where('dni', $dni)->first();
        
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }
        
        return response()->json($employee);
    }

    /**
     * Create a new employee
     */
    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8|unique:employees,dni',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:Masculino,Femenino',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'cargo' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'fecha_ingreso' => 'nullable|date',
            'tipo_contrato' => 'nullable|in:Nombrado,CAS,Locador,Practicante',
            'observaciones' => 'nullable|string',
        ], [
            'dni.unique' => 'Ya existe un empleado con ese DNI',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos',
        ]);

        $employee = Employee::create($validated);
        
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
        $employee = Employee::find($id);
        
        if (!$employee) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        $validated = $request->validate([
            'dni' => 'sometimes|string|size:8|unique:employees,dni,' . $id,
            'nombres' => 'sometimes|string|max:255',
            'apellidos' => 'sometimes|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:Masculino,Femenino',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'cargo' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'fecha_ingreso' => 'nullable|date',
            'tipo_contrato' => 'nullable|in:Nombrado,CAS,Locador,Practicante',
            'estado' => 'nullable|in:ACTIVO,INACTIVO,LICENCIA,VACACIONES',
            'observaciones' => 'nullable|string',
        ]);

        $employee->update($validated);
        
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
}
