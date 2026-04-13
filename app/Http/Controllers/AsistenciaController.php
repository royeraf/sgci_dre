<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MarcaAsistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AsistenciaController extends Controller
{
    /**
     * Página principal del módulo de asistencia.
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee?->load('person', 'direction', 'office', 'position');

        // Para admin/RRHH, listar empleados disponibles para filtrar
        $isAdmin = in_array($user->rol_id, ['ROL001', 'ROL009']);

        $employees = $isAdmin
            ? Employee::with('person')->where('estado', 'ACTIVO')->get()->map(fn($e) => [
                'id'             => $e->id,
                'nombre_completo'=> trim($e->full_name),
                'dni'            => $e->dni,
            ])->sortBy('nombre_completo')->values()
            : collect();

        return Inertia::render('Asistencia/Index', [
            'myEmployee' => $employee,
            'isAdmin'    => $isAdmin,
            'employees'  => $employees,
        ]);
    }

    /**
     * API: marcas del empleado autenticado (o de otro si admin).
     */
    public function getMarcas(Request $request)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->rol_id, ['ROL001', 'ROL009']);

        // Determinar el empleado a consultar
        if ($isAdmin && $request->filled('employee_id')) {
            $employee = Employee::findOrFail($request->employee_id);
        } else {
            $employee = $user->employee;
        }

        if (!$employee) {
            return response()->json([]);
        }

        $year  = $request->input('year',  now()->year);
        $month = $request->input('month', now()->month);

        $marcas = MarcaAsistencia::where('employee_id', $employee->id)
            ->delMes($year, $month)
            ->orderBy('fecha')
            ->get();

        return response()->json($marcas);
    }

    /**
     * API: guardar o actualizar marca de un día (solo admin/RRHH).
     */
    public function storeMarca(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->rol_id, ['ROL001', 'ROL009'])) {
            return response()->json(['message' => 'Sin permisos para registrar marcas.'], 403);
        }

        $validated = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'fecha'            => 'required|date',
            'entrada'          => 'nullable|date_format:H:i',
            'salida_mediodia'  => 'nullable|date_format:H:i',
            'retorno_mediodia' => 'nullable|date_format:H:i',
            'salida'           => 'nullable|date_format:H:i',
            'observaciones'    => 'nullable|string|max:500',
        ]);

        $validated['registrado_por'] = $user->id;

        $marca = MarcaAsistencia::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'fecha' => $validated['fecha']],
            $validated
        );

        return response()->json($marca->fresh(), 200);
    }

    /**
     * API: eliminar marca de un día (solo admin/RRHH).
     */
    public function deleteMarca(MarcaAsistencia $marca)
    {
        $user = Auth::user();

        if (!in_array($user->rol_id, ['ROL001', 'ROL009'])) {
            return response()->json(['message' => 'Sin permisos para eliminar marcas.'], 403);
        }

        $marca->delete();
        return response()->json(['message' => 'Marca eliminada correctamente.']);
    }
}
