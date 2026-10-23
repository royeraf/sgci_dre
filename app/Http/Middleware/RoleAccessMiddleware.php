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
        'utilitarios' => 'utilitarios',
        'planillas'   => 'planillas',
        'resoluciones' => 'resoluciones',
    ];

    /**
     * Módulos deshabilitados temporalmente por rol (feature flag).
     *
     * ROL012 (Empleado Portal) no accede al módulo "Papeletas de Salida" del
     * sistema principal: sus solicitudes se gestionan desde el Portal de
     * Empleados (/portal/papeletas). Aplica incluso si el módulo fue otorgado
     * explícitamente vía modulos_json.
     */
    private const ROLE_DISABLED_MODULES = [
        'ROL012' => ['papeletas'],
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

        $prefix = explode('/', $request->path())[0];
        $module = self::URL_MODULE_MAP[$prefix] ?? null;

        if ($module === 'resoluciones') {
            $action = $request->isMethod('POST') ? 'subir' : ($request->isMethod('PATCH') ? 'editar' : 'ver');
            if (app(\App\Services\ResolutionAccess::class)->allows($user, $action)) return $next($request);
            if ($request->expectsJson()) abort(403, 'No tiene permiso para esta acción en Resoluciones.');
            return redirect('/dashboard')->with('error', 'No tiene permiso para esta acción en Resoluciones.');
        }

        // Módulos deshabilitados temporalmente para el rol, sin importar los
        // permisos de rol o los módulos asignados al usuario.
        if ($module && in_array($module, self::ROLE_DISABLED_MODULES[$user->rol_id] ?? [], true)) {
            return redirect('/dashboard')->with('error', 'Esta sección está deshabilitada temporalmente para su rol.');
        }

        // Check if user role is in the allowed roles for this route
        if (in_array($user->rol_id, $roles)) {
            return $next($request);
        }

        // Check modulos_json: if the user has been explicitly granted this module, allow access
        if (!empty($user->modulos_json)) {
            if ($module && in_array($module, $user->modulos_json)) {
                return $next($request);
            }
        }

        return redirect('/dashboard')->with('error', 'No tiene permisos para acceder a esta sección.');
    }
}
