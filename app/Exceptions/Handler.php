<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            // Si el token expiró en el propio intento de login, recargar el
            // formulario en silencio (con token nuevo) sin alarmar al usuario
            if ($request->isMethod('POST') && $request->is('login')) {
                if ($request->hasHeader('X-Inertia')) {
                    return response('', 409)->withHeaders([
                        'X-Inertia-Location' => route('login'),
                    ]);
                }

                return redirect()->route('login');
            }

            if ($request->hasHeader('X-Inertia')) {
                // Force a full browser reload so the CSRF token is refreshed
                if ($request->hasSession()) {
                    $request->session()->flash('error', 'Tu sesión ha expirado. Por favor, ingresa nuevamente.');
                }
                return response('', 409)->withHeaders([
                    'X-Inertia-Location' => route('login'),
                ]);
            }

            return redirect()->route('login')
                ->with('error', 'Tu sesión ha expirado. Por favor, ingresa nuevamente.');
        }

        $response = parent::render($request, $e);

        if ($response->getStatusCode() === 404) {
            return Inertia::render('Error', ['status' => 404])
                ->toResponse($request)
                ->setStatusCode(404);
        }

        return $response;
    }
}
