<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Exámenes públicos: muchos participantes pueden rendir desde la misma red/IP
        // (aula, wifi institucional). Limitar solo por IP los bloquea entre sí, así que
        // la clave combina la IP con lo que identifica a CADA participante: el DNI en
        // /iniciar (aún no existe intento) y el propio intento en el resto de acciones.
        RateLimiter::for('examen-iniciar', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip() . '|' . $request->input('dni'));
        });

        RateLimiter::for('examen-intento', function (Request $request) {
            $intento = $request->route('intento');
            $intentoId = is_object($intento) ? $intento->getKey() : $intento;

            return Limit::perMinute(60)->by($request->ip() . '|' . $intentoId);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
