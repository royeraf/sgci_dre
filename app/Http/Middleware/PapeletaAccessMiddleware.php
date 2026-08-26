<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acceso al módulo de papeletas. Además de los roles fijos habituales
 * (admin, RR.HH., jefe inmediato, empleado portal), se concede acceso
 * automático a quien participe de la etapa "jefe" de alguna papeleta —
 * sin importar su rol — ya sea por estar designado como titular/suplente
 * de una oficina/dirección, o por haber sido elegido a mano por un
 * solicitante en una papeleta puntual.
 */
class PapeletaAccessMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if ($user->rol_id === 'ROL001') {
            return $next($request);
        }

        if (in_array($user->rol_id, $roles, true)) {
            return $next($request);
        }

        $employee = $user->employee;
        if ($employee && $employee->participaEnEtapaJefe()) {
            return $next($request);
        }

        if (!empty($user->modulos_json) && in_array('papeletas', $user->modulos_json, true)) {
            return $next($request);
        }

        return redirect('/dashboard')->with('error', 'No tiene permisos para acceder a esta sección.');
    }
}
