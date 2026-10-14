<?php

namespace App\Http\Middleware;

use App\Models\User;
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
     * Rutas accesibles mientras se completan los datos de contacto.
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
     * Orquesta el primer acceso: primero el cambio de contraseña y, luego, el
     * registro de los datos de contacto que falten.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Paso 1: cambio obligatorio de contraseña (tiene prioridad absoluta).
        if ($user->must_change_password) {
            if ($request->routeIs(self::RUTAS_PASSWORD)) {
                return $next($request);
            }

            return $this->redirigir($request, 'password.force');
        }

        // Paso 2: completar datos de contacto faltantes (correo o celular).
        $requiereDatos = self::requiereCompletarDatos($user)
            || ($user->rol_id !== 'ROL001' && $user->must_complete_profile);

        if ($requiereDatos && !$request->routeIs(self::RUTAS_PERFIL)) {
            return $this->redirigir($request, 'profile.complete');
        }

        return $next($request);
    }

    /**
     * Indica si al usuario le falta registrar el correo o el celular.
     * El administrador queda excluido de este paso.
     */
    public static function requiereCompletarDatos(User $user): bool
    {
        if ($user->rol_id === 'ROL001') {
            return false;
        }

        if (!$user->person_id) {
            return false;
        }

        return blank($user->person?->email) || blank($user->person?->telefono);
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
