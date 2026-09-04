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
            $isPortal = $request->is('portal') || $request->is('portal/*');
            $loginRoute = $isPortal ? route('portal.login') : route('login');

            if ($request->hasHeader('X-Inertia')) {
                if ($request->hasSession()) {
                    $request->session()->flash('error', 'Tu sesión o token de seguridad expiró por inactividad. Por favor, ingresa nuevamente.');
                }
                return response('', 409)->withHeaders([
                    'X-Inertia-Location' => $loginRoute,
                ]);
            }

            return redirect($loginRoute)
                ->with('error', 'Tu sesión o token de seguridad expiró por inactividad. Por favor, ingresa nuevamente.');
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
