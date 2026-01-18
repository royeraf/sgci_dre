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

        // Specific restriction for Jefe de Bienestar Social (ROL008)
        // If they are not in the allowed roles for this specific route, redirect them to their home
        if ($user->rol_id === 'ROL008') {
            return redirect('/bienestar')->with('error', 'No tiene permisos para acceder a esta sección.');
        }

        // Specific restriction for Jefe de RRHH (ROL009)
        // If they are not in the allowed roles for this specific route, redirect them to their home
        if ($user->rol_id === 'ROL009') {
            return redirect('/hr')->with('error', 'No tiene permisos para acceder a esta sección.');
        }

        // Specific restriction for Gestor de Citas (ROL010)
        // If they are not in the allowed roles for this specific route, redirect them to their home
        if ($user->rol_id === 'ROL010') {
            return redirect('/citas')->with('error', 'No tiene permisos para acceder a esta sección.');
        }

        // Default redirect for other roles
        return redirect('/dashboard')->with('error', 'No tiene permisos para acceder a esta sección.');
    }
}
