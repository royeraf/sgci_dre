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
            ->select('id', 'person_id', 'username', 'dni', 'name', 'apellidos', 'email', 'titulo', 'cargo', 'area', 'telefono', 'rol_id', 'modulos_json', 'tabs_json', 'is_active', 'ultimo_acceso', 'created_at')
            ->orderByRaw('COALESCE(name, (SELECT nombres FROM people WHERE people.id = users.person_id)) ASC')
            ->get()
            ->map(function ($user) {
                $p = $user->person;
                return [
                    'id'          => $user->id,
                    'person_id'   => $user->person_id,
                    'dni'         => $p?->dni ?? $user->getRawOriginal('dni'),
                    'name'        => $p?->nombres ?? $user->getRawOriginal('name'),
                    'apellidos'   => $p?->apellidos ?? $user->getRawOriginal('apellidos'),
                    'full_name'   => $user->full_name,
                    'email'       => $p?->email ?? $user->getRawOriginal('email'),
                    'titulo'      => $user->titulo,
                    'cargo'       => $user->cargo,
                    'area'        => $user->area,
                    'telefono'    => $p?->telefono ?? $user->getRawOriginal('telefono'),
                    'rol_id'      => $user->rol_id,
                    'rol_nombre'  => $user->customRole?->nombre,
                    'modulos_json' => $user->modulos_json,
                    'tabs_json'   => $user->tabs_json,
                    'is_active'   => $user->is_active,
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
            'dni'         => $p?->dni ?? $user->getRawOriginal('dni'),
            'name'        => $p?->nombres ?? $user->getRawOriginal('name'),
            'apellidos'   => $p?->apellidos ?? $user->getRawOriginal('apellidos'),
            'full_name'   => $user->full_name,
            'email'       => $p?->email ?? $user->getRawOriginal('email'),
            'titulo'      => $user->titulo,
            'cargo'       => $user->cargo,
            'area'        => $user->area,
            'telefono'    => $p?->telefono ?? $user->getRawOriginal('telefono'),
            'rol_id'      => $user->rol_id,
            'rol_nombre'  => $user->customRole?->nombre,
            'modulos_json' => $user->modulos_json,
            'tabs_json'   => $user->tabs_json,
            'is_active'   => $user->is_active,
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
     * Cuando se provee person_id los datos personales (dni, nombre, apellidos,
     * email, teléfono) se leen de la tabla people y NO se duplican en users.
     */
    public function store(Request $request)
    {
        $hasPersonId = $request->filled('person_id');

        // Reglas comunes
        $rules = [
            'person_id' => ['nullable', 'string', 'exists:people,id', 'unique:users,person_id'],
            'titulo'    => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'cargo'     => ['nullable', 'string', 'max:100'],
            'area'      => ['nullable', 'string', 'max:100'],
            'rol_id'    => ['required', 'exists:custom_roles,rol_id'],
            'modulos_json'   => ['nullable', 'array'],
            'modulos_json.*' => ['string'],
            'tabs_json'      => ['nullable', 'array'],
            'is_active'      => ['boolean'],
        ];

        $messages = [
            'person_id.unique'  => 'Esta persona ya tiene un usuario del sistema asignado',
            'email.email'       => 'El email debe ser válido',
            'email.unique'      => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed'=> 'Las contraseñas no coinciden',
            'rol_id.required'   => 'El rol es obligatorio',
            'rol_id.exists'     => 'El rol seleccionado no existe',
        ];

        // Sin persona vinculada: los datos personales se capturan en el formulario
        if (!$hasPersonId) {
            $rules['dni']      = ['required', 'string', 'size:8', 'unique:users,dni'];
            $rules['name']     = ['required', 'string', 'max:255'];
            $rules['apellidos']= ['nullable', 'string', 'max:100'];
            $rules['telefono'] = ['nullable', 'string', 'max:20'];
            $messages += [
                'dni.required' => 'El DNI es obligatorio',
                'dni.size'     => 'El DNI debe tener 8 dígitos',
                'dni.unique'   => 'Este DNI ya está registrado en usuarios',
                'name.required'=> 'El nombre es obligatorio',
            ];
        }

        $validated = $request->validate($rules, $messages);

        // Generar username único a partir del DNI (la clave de login)
        $baseDni  = $hasPersonId
            ? Person::findOrFail($validated['person_id'])->dni
            : $validated['dni'];
        $username = $this->generateUniqueUsername($baseDni);

        $userData = [
            'username'    => $username,
            'password'    => Hash::make($validated['password']),
            'rol_id'      => $validated['rol_id'],
            'modulos_json'=> !empty($validated['modulos_json']) ? $validated['modulos_json'] : null,
            'tabs_json'   => !empty($validated['tabs_json']) ? $validated['tabs_json'] : null,
            'is_active'   => $validated['is_active'] ?? true,
        ];

        if ($hasPersonId) {
            $person = Person::findOrFail($validated['person_id']);
            // Vinculado a RRHH: solo guardamos person_id y el DNI como clave de login
            // Nombre, apellidos, email, teléfono, título, cargo y área vienen de RRHH (no se duplican)
            $userData['person_id'] = $validated['person_id'];
            $userData['dni']       = $person->dni;
        } else {
            // Sin persona: guardar todos los datos directamente en users
            $userData['titulo']   = $validated['titulo'] ?? null;
            $userData['dni']      = $validated['dni'];
            $userData['name']     = $validated['name'];
            $userData['apellidos']= $validated['apellidos'] ?? null;
            $userData['email']    = $validated['email'] ?? null;
            $userData['telefono'] = $validated['telefono'] ?? null;
            $userData['cargo']    = $validated['cargo'] ?? null;
            $userData['area']     = $validated['area'] ?? null;
        }

        $user = User::create($userData);

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
     * Si el usuario tiene person_id, los datos personales (dni, nombre, apellidos,
     * teléfono) NO se actualizan aquí — viven en la tabla people.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $hasPersonId = !empty($user->person_id);

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
        ];

        $messages = [
            'rol_id.required'=> 'El rol es obligatorio',
            'rol_id.exists'  => 'El rol seleccionado no existe',
        ];

        // Sin persona vinculada: puede editar sus datos personales (incluido email)
        if (!$hasPersonId) {
            $rules['dni']      = ['required', 'string', 'size:8', Rule::unique('users')->ignore($user->id)];
            $rules['name']     = ['required', 'string', 'max:255'];
            $rules['apellidos']= ['nullable', 'string', 'max:100'];
            $rules['email']    = ['nullable', 'email', Rule::unique('users')->ignore($user->id)];
            $rules['telefono'] = ['nullable', 'string', 'max:20'];
            $messages += [
                'dni.required' => 'El DNI es obligatorio',
                'dni.size'     => 'El DNI debe tener 8 dígitos',
                'dni.unique'   => 'Este DNI ya está registrado',
                'name.required'=> 'El nombre es obligatorio',
                'email.email'  => 'El email debe ser válido',
                'email.unique' => 'Este email ya está registrado',
            ];
        }

        $validated = $request->validate($rules, $messages);

        $updateData = [
            'rol_id'      => $validated['rol_id'],
            'modulos_json'=> array_key_exists('modulos_json', $validated)
                ? (!empty($validated['modulos_json']) ? $validated['modulos_json'] : null)
                : $user->modulos_json,
            'tabs_json'   => array_key_exists('tabs_json', $validated)
                ? (!empty($validated['tabs_json']) ? $validated['tabs_json'] : null)
                : $user->tabs_json,
            'is_active'   => $validated['is_active'] ?? $user->is_active,
        ];

        if (!$hasPersonId) {
            $updateData['titulo']   = $validated['titulo'] ?? null;
            $updateData['dni']      = $validated['dni'];
            $updateData['name']     = $validated['name'];
            $updateData['apellidos']= $validated['apellidos'] ?? null;
            $updateData['email']    = $validated['email'] ?? null;
            $updateData['telefono'] = $validated['telefono'] ?? null;
            $updateData['cargo']    = $validated['cargo'] ?? null;
            $updateData['area']     = $validated['area'] ?? null;
        }

        $user->update($updateData);

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
