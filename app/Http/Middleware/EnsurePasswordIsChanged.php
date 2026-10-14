<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Rutas accesibles durante el cambio obligatorio de contraseña.
     *
     * @var array<int, string>
     */
    private const RUTAS_PASSWORD = [
        'password.force',
        'password.force.update',
        'logout',
        'portal.logout',
    ];

    /**
     * Rutas accesibles mientras se completan los datos pendientes del perfil.
     *
     * @var array<int, string>
     */
    private const RUTAS_PERFIL = [
        'profile.complete',
        'profile.complete.update',
        'logout',
        'portal.logout',
    ];

    /**
     * Orquesta el primer acceso en dos pasos: primero el cambio de contraseña y,
     * luego, si faltan datos de contacto, su registro.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Paso 1: cambio obligatorio de contraseña.
        if ($user->must_change_password && !$request->routeIs(self::RUTAS_PASSWORD)) {
            return $this->redirigir($request, 'password.force');
        }

        // Paso 2: completar datos de contacto faltantes.
        if ($user->must_complete_profile && !$request->routeIs(self::RUTAS_PERFIL)) {
            return $this->redirigir($request, 'profile.complete');
        }

        return $next($request);
    }

    /**
     * Redirige a la pantalla correspondiente (o responde JSON en XHR).
     */
    private function redirigir(Request $request, string $ruta): Response
    {
        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Debe completar su registro antes de continuar.',
            ], 423);
        }

        return redirect()->route($ruta);
    }
}
