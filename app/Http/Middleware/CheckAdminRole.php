<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user has admin role using Spatie Permission
        // or custom role with high access level
        $isAdmin = $user->hasRole('admin') ||
                   $user->hasRole('administrador') ||
                   ($user->customRole && ($user->customRole->nivel_acceso >= 9 || $user->customRole->codigo === 'ADMIN'));

        if ($isAdmin) {
            return $next($request);
        }

        // If this is an AJAX/API request, return JSON error
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No tienes permisos para acceder a esta sección.'
            ], 403);
        }

        // For regular requests, redirect to dashboard with error
        return redirect()->route('dashboard')->with('error', 'No tienes permisos para acceder a esta sección.');
    }
}
