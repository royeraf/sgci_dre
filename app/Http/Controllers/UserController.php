<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomRole;
use App\Models\HRArea;
use App\Models\HRPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display the users management page.
     */
    public function index()
    {
        return Inertia::render('Users/Index');
    }

    /**
     * Get all users with their roles.
     */
    public function getUsers()
    {
        $users = User::with('customRole')
            ->select('id', 'dni', 'name', 'apellidos', 'email', 'titulo', 'cargo', 'area', 'telefono', 'rol_id', 'is_active', 'ultimo_acceso', 'created_at')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'dni' => $user->dni,
                    'name' => $user->name,
                    'apellidos' => $user->apellidos,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'titulo' => $user->titulo,
                    'cargo' => $user->cargo,
                    'area' => $user->area,
                    'telefono' => $user->telefono,
                    'rol_id' => $user->rol_id,
                    'rol_nombre' => $user->customRole?->nombre,
                    'is_active' => $user->is_active,
                    'ultimo_acceso' => $user->ultimo_acceso?->format('Y-m-d H:i:s'),
                    'created_at' => $user->created_at->format('Y-m-d'),
                ];
            });

        return response()->json($users);
    }

    /**
     * Get a single user by ID.
     */
    public function getUser($id)
    {
        $user = User::with('customRole')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'dni' => $user->dni,
            'name' => $user->name,
            'apellidos' => $user->apellidos,
            'email' => $user->email,
            'titulo' => $user->titulo,
            'cargo' => $user->cargo,
            'area' => $user->area,
            'telefono' => $user->telefono,
            'rol_id' => $user->rol_id,
            'rol_nombre' => $user->customRole?->nombre,
            'is_active' => $user->is_active,
            'ultimo_acceso' => $user->ultimo_acceso,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Get all active roles.
     */
    public function getRoles()
    {
        $roles = CustomRole::active()
            ->select('rol_id', 'codigo', 'nombre', 'descripcion', 'nivel_acceso')
            ->orderBy('nombre')
            ->get();

        return response()->json($roles);
    }

    /**
     * Get all areas for user assignment.
     */
    public function getAreas()
    {
        $areas = HRArea::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json($areas);
    }

    /**
     * Get all positions for user assignment.
     */
    public function getPositions()
    {
        $positions = HRPosition::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json($positions);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dni' => ['required', 'string', 'size:8', 'unique:users,dni'],
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:100'],
            'titulo' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => ['required', 'exists:custom_roles,rol_id'],
            'is_active' => ['boolean'],
        ], [
            'dni.required' => 'El DNI es obligatorio',
            'dni.size' => 'El DNI debe tener 8 dígitos',
            'dni.unique' => 'Este DNI ya está registrado',
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'rol_id.required' => 'El rol es obligatorio',
            'rol_id.exists' => 'El rol seleccionado no existe',
        ]);

        $user = User::create([
            'dni' => $validated['dni'],
            'name' => $validated['name'],
            'apellidos' => $validated['apellidos'] ?? null,
            'titulo' => $validated['titulo'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'cargo' => $validated['cargo'] ?? null,
            'area' => $validated['area'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'rol_id' => $validated['rol_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user->load('customRole'),
        ], 201);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'dni' => ['required', 'string', 'size:8', Rule::unique('users')->ignore($user->id)],
            'name' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:100'],
            'titulo' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'cargo' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => ['required', 'exists:custom_roles,rol_id'],
            'is_active' => ['boolean'],
        ], [
            'dni.required' => 'El DNI es obligatorio',
            'dni.size' => 'El DNI debe tener 8 dígitos',
            'dni.unique' => 'Este DNI ya está registrado',
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'rol_id.required' => 'El rol es obligatorio',
            'rol_id.exists' => 'El rol seleccionado no existe',
        ]);

        $user->update([
            'dni' => $validated['dni'],
            'name' => $validated['name'],
            'apellidos' => $validated['apellidos'] ?? null,
            'titulo' => $validated['titulo'] ?? null,
            'email' => $validated['email'],
            'cargo' => $validated['cargo'] ?? null,
            'area' => $validated['area'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'rol_id' => $validated['rol_id'],
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user->load('customRole'),
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente',
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return response()->json([
            'message' => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting the currently authenticated user
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'user' => ['No puedes eliminar tu propia cuenta'],
            ]);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente',
        ]);
    }

    /**
     * Get users summary/statistics.
     */
    public function getSummary()
    {
        $total = User::count();
        $active = User::where('is_active', true)->count();
        $inactive = User::where('is_active', false)->count();
        $byRole = User::with('customRole')
            ->get()
            ->groupBy('rol_id')
            ->map(function ($users, $rolId) {
                return [
                    'rol_id' => $rolId,
                    'rol_nombre' => $users->first()->customRole?->nombre ?? 'Sin rol',
                    'count' => $users->count(),
                ];
            })
            ->values();

        return response()->json([
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'by_role' => $byRole,
        ]);
    }
}
