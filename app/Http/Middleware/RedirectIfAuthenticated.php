<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                if ($user->rol_id === 'ROL011') return redirect('/papeletas');
                if ($user->rol_id === 'ROL010') return redirect('/citas');
                if ($user->rol_id === 'ROL009') return redirect('/hr');
                if ($user->rol_id === 'ROL008') return redirect('/bienestar');
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
