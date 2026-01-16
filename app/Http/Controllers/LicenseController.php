<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LicenseController extends Controller
{
    /**
     * Display the Bienestar Social / Licencias page
     */
    public function index()
    {
        return Inertia::render('Bienestar/Index');
    }

    /**
     * Get all licenses with employee data
     */
    public function getLicenses()
    {
        $licenses = License::with('employee')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($license) {
                return [
                    'id' => $license->id,
                    'dni' => $license->dni,
                    'tipo_licencia' => $license->tipo_licencia,
                    'motivo' => $license->motivo,
                    'fecha_inicio' => $license->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $license->fecha_fin?->format('Y-m-d'),
                    'dias_solicitados' => $license->dias_solicitados,
                    'estado' => $license->estado,
                    'observaciones' => $license->observaciones,
                    'nombres' => $license->employee->nombres ?? '',
                    'apellidos' => $license->employee->apellidos ?? '',
                    'cargo' => $license->employee->cargo ?? '',
                    'area' => $license->employee->area ?? '',
                ];
            });
        
        return response()->json($licenses);
    }

    /**
     * Get employees with their license balance
     */
    public function getEmployeesBalance()
    {
        $employees = Employee::orderBy('apellidos')
            ->get()
            ->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'dni' => $emp->dni,
                    'nombres' => $emp->nombres,
                    'apellidos' => $emp->apellidos,
                    'cargo' => $emp->cargo,
                    'area' => $emp->area,
                    'dias_totales' => $emp->licencias_totales,
                    'dias_usados' => $emp->licencias_usadas,
                    'dias_disponibles' => $emp->licencias_totales - $emp->licencias_usadas,
                ];
            });
        
        return response()->json($employees);
    }

    /**
     * Get license summary statistics
     */
    public function getSummary()
    {
        $currentYear = now()->year;
        
        $totalEmployees = Employee::count();
        $thisYearLicenses = License::whereYear('fecha_inicio', $currentYear)->count();
        $totalLicenses = License::count();
        $noDaysLeft = Employee::whereRaw('licencias_totales - licencias_usadas <= 0')->count();

        $byType = [
            'enfermedad' => License::where('tipo_licencia', 'Enfermedad')->whereYear('fecha_inicio', $currentYear)->count(),
            'maternidad' => License::where('tipo_licencia', 'Maternidad')->whereYear('fecha_inicio', $currentYear)->count(),
            'paternidad' => License::where('tipo_licencia', 'Paternidad')->whereYear('fecha_inicio', $currentYear)->count(),
            'personal' => License::where('tipo_licencia', 'Personal')->whereYear('fecha_inicio', $currentYear)->count(),
            'otros' => License::where('tipo_licencia', 'Otros')->whereYear('fecha_inicio', $currentYear)->count(),
        ];

        return response()->json([
            'total_empleados' => $totalEmployees,
            'total_licencias' => $totalLicenses,
            'licencias_este_año' => $thisYearLicenses,
            'empleados_sin_dias' => $noDaysLeft,
            'por_tipo' => $byType
        ]);
    }

    /**
     * Get employee by DNI with balance
     */
    public function getEmployeeByDni(string $dni)
    {
        $emp = Employee::where('dni', $dni)->first();
        
        if (!$emp) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }
        
        return response()->json([
            'id' => $emp->id,
            'dni' => $emp->dni,
            'nombres' => $emp->nombres,
            'apellidos' => $emp->apellidos,
            'cargo' => $emp->cargo,
            'area' => $emp->area,
            'dias_disponibles' => $emp->licencias_totales - $emp->licencias_usadas,
        ]);
    }

    /**
     * Register a new license
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => 'required|string|size:8',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'tipo_licencia' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'motivo' => 'nullable|string',
            'cargo' => 'nullable|string',
            'area' => 'nullable|string',
        ]);

        $employee = Employee::where('dni', $validated['dni'])->first();

        if (!$employee) {
            // Create employee if not exists (following old project logic)
            $employee = Employee::create([
                'dni' => $validated['dni'],
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'cargo' => $validated['cargo'] ?? null,
                'area' => $validated['area'] ?? null,
                'licencias_totales' => 20,
                'licencias_usadas' => 0,
            ]);
        }

        // Calculate days
        $start = Carbon::parse($validated['fecha_inicio']);
        $end = Carbon::parse($validated['fecha_fin']);
        $daysRequested = $start->diffInDays($end) + 1;

        // Check availability
        $available = $employee->licencias_totales - $employee->licencias_usadas;
        if ($daysRequested > $available) {
            return response()->json([
                'message' => "El empleado solo cuenta con {$available} días disponibles."
            ], 422);
        }

        return DB::transaction(function () use ($validated, $employee, $daysRequested) {
            $license = License::create([
                'employee_id' => $employee->id,
                'dni' => $employee->dni,
                'tipo_licencia' => $validated['tipo_licencia'],
                'motivo' => $validated['motivo'] ?? null,
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'dias_solicitados' => $daysRequested,
                'estado' => 'APROBADO',
                'created_by' => auth()->user()->name ?? 'Sistema',
            ]);

            // Update employee used days
            $employee->increment('licencias_usadas', $daysRequested);

            return response()->json([
                'message' => 'Licencia registrada correctamente',
                'id' => $license->id
            ], 201);
        });
    }
}
