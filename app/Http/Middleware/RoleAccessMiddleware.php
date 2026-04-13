<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    /**
     * Mapeo de prefijo de URL → clave de módulo en modulos_json.
     */
    private const URL_MODULE_MAP = [
        'occurrences' => 'ocurrencias',
        'ocurrencias' => 'ocurrencias',
        'entry-exits' => 'control_personal',
        'visitors'    => 'visitas',
        'vehicles'    => 'vehiculos',
        'assets'      => 'patrimonio',
        'citas'       => 'secretaria',
        'bienestar'   => 'bienestar',
        'hr'          => 'recursos_humanos',
        'papeletas'   => 'papeletas',
        'asistencia'  => 'asistencia',
    ];

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Admin always has access
        if ($user->rol_id === 'ROL001') {
            return $next($request);
        }

        // Check if user role is in the allowed roles for this route
        if (in_array($user->rol_id, $roles)) {
            return $next($request);
        }

        // Check modulos_json: if the user has been explicitly granted this module, allow access
        if (!empty($user->modulos_json)) {
            $prefix = explode('/', $request->path())[0];
            $module = self::URL_MODULE_MAP[$prefix] ?? null;
            if ($module && in_array($module, $user->modulos_json)) {
                return $next($request);
            }
        }

        return redirect('/dashboard')->with('error', 'No tiene permisos para acceder a esta sección.');
    }
}
