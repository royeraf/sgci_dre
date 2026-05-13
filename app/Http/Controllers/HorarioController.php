<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HorarioController extends Controller
{
    private function isAdmin(): bool
    {
        return in_array(Auth::user()->rol_id, ['ROL001', 'ROL009']);
    }

    // ── Listado ────────────────────────────────────────────────────────────

    public function index()
    {
        return response()->json(
            Horario::withCount('employees')
                ->orderBy('activo', 'desc')
                ->orderBy('nombre')
                ->get()
                ->map(fn($h) => [
                    'id'             => $h->id,
                    'nombre'         => $h->nombre,
                    'entrada_manana' => substr($h->entrada_manana, 0, 5),
                    'salida_manana'  => substr($h->salida_manana,  0, 5),
                    'entrada_tarde'  => $h->entrada_tarde  ? substr($h->entrada_tarde,  0, 5) : null,
                    'salida_tarde'   => $h->salida_tarde   ? substr($h->salida_tarde,   0, 5) : null,
                    'descripcion'    => $h->descripcion,
                    'activo'         => $h->activo,
                    'resumen'        => $h->resumen,
                    'employees_count'=> $h->employees_count,
                ])
        );
    }

    // ── Crear ──────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        if (!$this->isAdmin()) return response()->json(['message' => 'Sin permisos.'], 403);

        $validator = Validator::make($request->all(), [
            'nombre'         => 'required|string|max:100',
            'entrada_manana' => 'required|date_format:H:i',
            'salida_manana'  => 'required|date_format:H:i',
            'entrada_tarde'  => 'nullable|date_format:H:i',
            'salida_tarde'   => 'nullable|date_format:H:i',
            'descripcion'    => 'nullable|string|max:500',
        ], [
            'nombre.required'         => 'El nombre es obligatorio.',
            'entrada_manana.required' => 'La hora de entrada mañana es obligatoria.',
            'entrada_manana.date_format' => 'Formato inválido (HH:MM).',
            'salida_manana.required'  => 'La hora de salida mañana es obligatoria.',
            'salida_manana.date_format'  => 'Formato inválido (HH:MM).',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()->toArray()], 422);
        }

        $horario = Horario::create(array_merge($validator->validated(), ['activo' => true]));

        return response()->json(array_merge($horario->toArray(), [
            'resumen' => $horario->resumen,
            'employees_count' => 0,
        ]), 201);
    }

    // ── Actualizar ─────────────────────────────────────────────────────────

    public function update(Request $request, Horario $horario)
    {
        if (!$this->isAdmin()) return response()->json(['message' => 'Sin permisos.'], 403);

        $validator = Validator::make($request->all(), [
            'nombre'         => 'required|string|max:100',
            'entrada_manana' => 'required|date_format:H:i',
            'salida_manana'  => 'required|date_format:H:i',
            'entrada_tarde'  => 'nullable|date_format:H:i',
            'salida_tarde'   => 'nullable|date_format:H:i',
            'descripcion'    => 'nullable|string|max:500',
            'activo'         => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()->toArray()], 422);
        }

        $horario->update($validator->validated());
        $horario->loadCount('employees');

        return response()->json(array_merge($horario->toArray(), [
            'resumen' => $horario->resumen,
            'employees_count' => $horario->employees_count,
        ]));
    }

    // ── Eliminar ───────────────────────────────────────────────────────────

    public function destroy(Horario $horario)
    {
        if (!$this->isAdmin()) return response()->json(['message' => 'Sin permisos.'], 403);

        if ($horario->employees()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar: hay empleados asignados a este horario.'], 409);
        }

        $horario->delete();
        return response()->json(['message' => 'Horario eliminado.']);
    }

    // ── Asignar horario a un empleado ──────────────────────────────────────

    public function assignEmployee(Request $request)
    {
        if (!$this->isAdmin()) return response()->json(['message' => 'Sin permisos.'], 403);

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'horario_id'  => 'nullable|exists:horarios,id',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $employee->update(['horario_id' => $request->horario_id]);

        return response()->json([
            'message'    => 'Horario asignado correctamente.',
            'horario_id' => $employee->horario_id,
        ]);
    }

    // ── Empleados de un horario ────────────────────────────────────────────

    public function employees(Horario $horario)
    {
        $employees = $horario->employees()
            ->with('person', 'direction')
            ->where('estado', 'ACTIVO')
            ->get()
            ->map(fn($e) => [
                'id'             => $e->id,
                'nombre_completo'=> $e->full_name,
                'dni'            => $e->dni,
                'direction'      => $e->direction?->nombre,
            ]);

        return response()->json($employees);
    }
}
