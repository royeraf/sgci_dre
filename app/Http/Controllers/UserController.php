<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Person;
use App\Models\CustomRole;
use App\Models\HrDirection;
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
        $users = User::with(['customRole', 'person', 'employee.position', 'employee.direction'])
            ->select('id', 'person_id', 'username', 'cargo', 'area', 'rol_id', 'modulos_json', 'tabs_json', 'is_active', 'must_change_password', 'ultimo_acceso', 'created_at')
            ->orderByRaw('(SELECT nombres FROM people WHERE people.id = users.person_id) ASC')
            ->get()
            ->map(function ($user) {
                $p = $user->person;
                $esRrhh = (bool) $user->employee;
                return [
                    'id'          => $user->id,
                    'person_id'   => $user->person_id,
                    'dni'         => $p?->dni,
                    'name'        => $p?->nombres,
                    'apellidos'   => $p?->apellidos,
                    'full_name'   => $user->full_name,
                    'email'       => $p?->email,
                    'titulo'      => $p?->titulo,
                    'cargo'       => $user->cargo,
                    'area'        => $user->area,
                    'telefono'    => $p?->telefono,
                    'rol_id'      => $user->rol_id,
                    'rol_nombre'  => $user->customRole?->nombre,
                    'modulos_json' => $user->modulos_json,
                    'tabs_json'   => $user->tabs_json,
                    'is_active'   => $user->is_active,
                    'must_change_password' => (bool) $user->must_change_password,
                    'es_rrhh'     => $esRrhh,
                    'ultimo_acceso' => $user->ultimo_acceso?->format('Y-m-d H:i:s'),
                    'created_at'  => $user->created_at->format('Y-m-d'),
                    'person'      => $p ? [
                        'nombres'   => $p->nombres,
                        'apellidos' => $p->apellidos,
                        'dni'       => $p->dni,
                        'email'     => $p->email,
                        'telefono'  => $p->telefono,
                    ] : null,
                ];
            });

        return response()->json($users);
    }

    /**
     * Get a single user by ID.
     */
    public function getUser($id)
    {
        $user = User::with(['customRole', 'person', 'employee.position', 'employee.direction'])->findOrFail($id);
        $p = $user->person;

        return response()->json([
            'id'          => $user->id,
            'person_id'   => $user->person_id,
            'dni'         => $p?->dni,
            'name'        => $p?->nombres,
            'apellidos'   => $p?->apellidos,
            'full_name'   => $user->full_name,
            'email'       => $p?->email,
            'titulo'      => $p?->titulo,
            'cargo'       => $user->cargo,
            'area'        => $user->area,
            'telefono'    => $p?->telefono,
            'rol_id'      => $user->rol_id,
            'rol_nombre'  => $user->customRole?->nombre,
            'modulos_json' => $user->modulos_json,
            'tabs_json'   => $user->tabs_json,
            'is_active'   => $user->is_active,
            'must_change_password' => (bool) $user->must_change_password,
            'es_rrhh'     => (bool) $user->employee,
            'ultimo_acceso' => $user->ultimo_acceso,
            'created_at'  => $user->created_at,
            'person'      => $p ? [
                'nombres'   => $p->nombres,
                'apellidos' => $p->apellidos,
                'dni'       => $p->dni,
                'email'     => $p->email,
                'telefono'  => $p->telefono,
            ] : null,
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
        $areas = HrDirection::select('id', 'nombre')
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
     *
     * Si se provee person_id se vincula a una persona existente (RRHH). Si no,
     * se crea una nueva persona en `people` con los datos capturados. Los datos
     * personales nunca se guardan en `users`.
     */
    public function store(Request $request)
    {
        $hasPersonId = $request->filled('person_id');

        // Reglas comunes
        $rules = [
            'person_id' => ['nullable', 'string', 'exists:people,id', 'unique:users,person_id'],
            'titulo'    => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:150'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'cargo'     => ['nullable', 'string', 'max:100'],
            'area'      => ['nullable', 'string', 'max:100'],
            'rol_id'    => ['required', 'exists:custom_roles,rol_id'],
            'modulos_json'   => ['nullable', 'array'],
            'modulos_json.*' => ['string'],
            'tabs_json'      => ['nullable', 'array'],
            'is_active'      => ['boolean'],
            'must_change_password' => ['boolean'],
        ];

        $messages = [
            'person_id.unique'  => 'Esta persona ya tiene un usuario del sistema asignado',
            'email.email'       => 'El email debe ser válido',
            'password.required' => 'La contraseña es obligatoria',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed'=> 'Las contraseñas no coinciden',
            'rol_id.required'   => 'El rol es obligatorio',
            'rol_id.exists'     => 'El rol seleccionado no existe',
        ];

        // Sin persona vinculada: se crea una persona nueva en people.
        if (!$hasPersonId) {
            $rules['dni']      = ['required', 'string', 'size:8', 'unique:people,dni'];
            $rules['name']     = ['required', 'string', 'max:255'];
            $rules['apellidos']= ['nullable', 'string', 'max:100'];
            $rules['telefono'] = ['nullable', 'string', 'max:20'];
            $messages += [
                'dni.required' => 'El DNI es obligatorio',
                'dni.size'     => 'El DNI debe tener 8 dígitos',
                'dni.unique'   => 'Este DNI ya está registrado',
                'name.required'=> 'El nombre es obligatorio',
            ];
        }

        $validated = $request->validate($rules, $messages);

        // Persona: existente (RRHH) o nueva.
        if ($hasPersonId) {
            $person = Person::findOrFail($validated['person_id']);
        } else {
            $person = Person::create([
                'dni'       => $validated['dni'],
                'nombres'   => $validated['name'],
                'apellidos' => $validated['apellidos'] ?? '',
                'titulo'    => $validated['titulo'] ?? null,
                'email'     => $validated['email'] ?? null,
                'telefono'  => $validated['telefono'] ?? null,
                'tipo'      => 'INTERNO',
                'is_active' => true,
            ]);
        }

        // Generar username único a partir del DNI (la clave de login)
        $username = $this->generateUniqueUsername($person->dni);

        $user = User::create([
            'person_id'    => $person->id,
            'username'     => $username,
            'password'     => Hash::make($validated['password']),
            'rol_id'       => $validated['rol_id'],
            'cargo'        => $validated['cargo'] ?? null,
            'area'         => $validated['area'] ?? null,
            'modulos_json' => !empty($validated['modulos_json']) ? $validated['modulos_json'] : null,
            'tabs_json'    => !empty($validated['tabs_json']) ? $validated['tabs_json'] : null,
            'is_active'    => $validated['is_active'] ?? true,
            // Por defecto, todo usuario nuevo debe cambiar la contraseña en su primer acceso.
            'must_change_password' => $validated['must_change_password'] ?? true,
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user'    => $user->load(['customRole', 'person']),
        ], 201);
    }

    /**
     * Genera un username único basado en una cadena base.
     */
    private function generateUniqueUsername(string $base): string
    {
        $base     = strtolower(preg_replace('/[^a-zA-Z0-9._]/', '', $base));
        $username = $base;
        $counter  = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }
        return $username;
    }

    /**
     * Update an existing user.
     *
     * Si la persona está vinculada a RRHH (tiene empleado), sus datos personales
     * se editan desde el módulo de Recursos Humanos. Si no, se editan en `people`
     * a través de este formulario.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $editaPersona = !$user->employee()->exists();

        // Reglas comunes
        $rules = [
            'titulo'         => ['nullable', 'string', 'max:20'],
            'cargo'          => ['nullable', 'string', 'max:100'],
            'area'           => ['nullable', 'string', 'max:100'],
            'rol_id'         => ['required', 'exists:custom_roles,rol_id'],
            'modulos_json'   => ['nullable', 'array'],
            'modulos_json.*' => ['string'],
            'tabs_json'      => ['nullable', 'array'],
            'is_active'      => ['boolean'],
            'must_change_password' => ['boolean'],
        ];

        $messages = [
            'rol_id.required'=> 'El rol es obligatorio',
            'rol_id.exists'  => 'El rol seleccionado no existe',
        ];

        // Persona no vinculada a RRHH: puede editar sus datos personales.
        if ($editaPersona) {
            $rules['dni']      = ['required', 'string', 'size:8', Rule::unique('people', 'dni')->ignore($user->person_id)];
            $rules['name']     = ['required', 'string', 'max:255'];
            $rules['apellidos']= ['nullable', 'string', 'max:100'];
            $rules['email']    = ['nullable', 'email', 'max:150'];
            $rules['telefono'] = ['nullable', 'string', 'max:20'];
            $messages += [
                'dni.required' => 'El DNI es obligatorio',
                'dni.size'     => 'El DNI debe tener 8 dígitos',
                'dni.unique'   => 'Este DNI ya está registrado',
                'name.required'=> 'El nombre es obligatorio',
                'email.email'  => 'El email debe ser válido',
            ];
        }

        $validated = $request->validate($rules, $messages);

        $user->update([
            'rol_id'      => $validated['rol_id'],
            'cargo'       => array_key_exists('cargo', $validated) ? $validated['cargo'] : $user->cargo,
            'area'        => array_key_exists('area', $validated) ? $validated['area'] : $user->area,
            'modulos_json'=> array_key_exists('modulos_json', $validated)
                ? (!empty($validated['modulos_json']) ? $validated['modulos_json'] : null)
                : $user->modulos_json,
            'tabs_json'   => array_key_exists('tabs_json', $validated)
                ? (!empty($validated['tabs_json']) ? $validated['tabs_json'] : null)
                : $user->tabs_json,
            'is_active'   => $validated['is_active'] ?? $user->is_active,
            'must_change_password' => $validated['must_change_password'] ?? $user->must_change_password,
        ]);

        if ($editaPersona && $user->person) {
            $user->person->update([
                'dni'       => $validated['dni'],
                'nombres'   => $validated['name'],
                'apellidos' => $validated['apellidos'] ?? '',
                'titulo'    => $validated['titulo'] ?? null,
                'email'     => $validated['email'] ?? null,
                'telefono'  => $validated['telefono'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user'    => $user->load(['customRole', 'person', 'employee.position', 'employee.direction']),
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
            // Al resetear la contraseña desde el admin, el usuario deberá
            // cambiarla en su siguiente inicio de sesión.
            'must_change_password' => true,
            'password_changed_at'  => null,
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente. El usuario deberá cambiarla al iniciar sesión.',
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
     * Get all roles (including inactive) for the roles manager.
     */
    public function getAllRoles()
    {
        $roles = CustomRole::withCount('users')
            ->select('rol_id', 'codigo', 'nombre', 'descripcion', 'nivel_acceso', 'activo', 'created_at')
            ->orderBy('nombre')
            ->get();

        return response()->json($roles);
    }

    /**
     * Store a new role.
     */
    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:100', 'unique:custom_roles,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'nivel_acceso'=> ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $last = CustomRole::orderByRaw("CAST(SUBSTRING(rol_id, 4) AS UNSIGNED) DESC")->first();
        $nextNum = $last ? ((int) substr($last->rol_id, 3)) + 1 : 1;
        $rolId = 'ROL' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        $role = CustomRole::create([
            'rol_id'      => $rolId,
            'codigo'      => strtoupper(substr(preg_replace('/\s+/', '_', $data['nombre']), 0, 20)),
            'nombre'      => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'nivel_acceso'=> $data['nivel_acceso'],
            'activo'      => true,
        ]);

        return response()->json(['message' => 'Rol creado exitosamente', 'role' => $role], 201);
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Request $request, string $rolId)
    {
        $role = CustomRole::findOrFail($rolId);

        $data = $request->validate([
            'nombre'      => ['required', 'string', 'max:100', Rule::unique('custom_roles', 'nombre')->ignore($rolId, 'rol_id')],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'nivel_acceso'=> ['required', 'integer', 'min:1', 'max:10'],
            'activo'      => ['boolean'],
        ]);

        $role->update($data);

        return response()->json(['message' => 'Rol actualizado exitosamente', 'role' => $role]);
    }

    /**
     * Toggle role active status.
     */
    public function toggleRoleStatus(string $rolId)
    {
        $role = CustomRole::findOrFail($rolId);
        $role->update(['activo' => !$role->activo]);

        return response()->json(['message' => 'Estado actualizado', 'activo' => $role->activo]);
    }

    /**
     * Delete a role (only if no users assigned).
     */
    public function destroyRole(string $rolId)
    {
        $role = CustomRole::withCount('users')->findOrFail($rolId);

        if ($role->users_count > 0) {
            return response()->json([
                'message' => "No se puede eliminar: {$role->users_count} usuario(s) tienen este rol asignado."
            ], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado exitosamente']);
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
