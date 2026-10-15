<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Employee;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $request->user() ? (function () use ($request) {
                    $user = $request->user()->loadMissing('person', 'customRole', 'employee.position');
                    return [
                        'id'           => $user->id,
                        'username'     => $user->username,
                        'name'         => $user->name,
                        'apellidos'    => $user->apellidos,
                        'full_name'    => $user->full_name,
                        'dni'          => $user->dni,
                        'email'        => $user->email,
                        'telefono'     => $user->telefono,
                        'titulo'       => $user->titulo,
                        'cargo'        => $user->cargo,
                        'area'         => $user->area,
                        'rol_id'       => $user->rol_id,
                        'modulos_json' => $user->modulos_json,
                        'tabs_json'    => $user->tabs_json,
                        'must_change_password' => (bool) $user->must_change_password,
                        'customRole'   => $user->customRole,
                        // El apartado "Mis Boletas" se muestra a todo usuario
                        // con empleado vinculado, sin exigir módulos del admin.
                        'has_employee' => $user->person_id
                            ? Employee::where('person_id', $user->person_id)->exists()
                            : false,
                    ];
                })() : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'new_visit_id' => fn () => $request->session()->get('new_visit_id'),
            ],
        ];
    }
}
