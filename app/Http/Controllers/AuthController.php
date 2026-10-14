<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show the login page.
     */
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'dni' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'dni.required' => 'El DNI o usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $identifier = trim($request->input('dni'));
        $throttleKey = Str::transliterate(Str::lower($identifier) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            throw ValidationException::withMessages([
                'credentials' => 'Demasiados intentos fallidos. Por favor espere ' . $minutes . ' '
                    . ($minutes === 1 ? 'minuto' : 'minutos') . ' antes de volver a intentar.',
            ]);
        }

        // Find user by DNI, username, or email (incluye el DNI/correo de la
        // persona vinculada en people, que es la fuente canónica).
        $user = User::findByIdentifier($identifier);

        // Auto-provision if active employee exists and logging in with DNI as initial password
        if (!$user && preg_match('/^[0-9]{8}$/', $identifier)) {
            $employee = Employee::activos()
                ->whereHas('person', fn ($q) => $q->where('dni', $identifier))
                ->first();

            if ($employee && $request->password === $identifier) {
                // La persona puede ya tener cuenta con otro identificador
                // (users.person_id es UNIQUE): nunca duplicar.
                $user = User::where('person_id', $employee->person_id)->first();

                if (!$user) {
                    try {
                        $user = User::create([
                            'person_id' => $employee->person_id,
                            'username'  => $identifier,
                            'password'  => Hash::make($request->password),
                            'rol_id'    => 'ROL012',
                            'is_active' => true,
                            // Primer acceso: deberá cambiar la contraseña (inicial = DNI)
                            // y completar correo y celular.
                            'must_change_password' => true,
                        ]);
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        // Carrera (doble envío): la cuenta ya se creó.
                        $user = User::where('person_id', $employee->person_id)->first();
                    }
                }
            }
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 120);
            throw ValidationException::withMessages([
                'credentials' => 'Las credenciales proporcionadas son incorrectas.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'credentials' => 'Su cuenta está desactivada. Contacte al administrador.',
            ]);
        }

        // Login the user with optional remember me
        Auth::login($user, $request->boolean('remember'));

        // Update last access
        $user->update(['ultimo_acceso' => now()]);

        // Log the action
        AuditLog::log(
            $user->id,
            'Login',
            'Usuario inició sesión en el sistema',
        );

        $request->session()->regenerate();

        // Primer acceso obligatorio: la contraseña inicial (DNI) debe cambiarse.
        if ($user->must_change_password) {
            return redirect()->route('password.force');
        }

        // Redirect based on role
        if ($user->rol_id === 'ROL008') {
            return redirect()->intended('/bienestar');
        }

        if ($user->rol_id === 'ROL009') {
            return redirect()->intended('/hr');
        }

        if ($user->rol_id === 'ROL010') {
            return redirect()->intended('/citas');
        }

        if ($user->rol_id === 'ROL011') {
            return redirect()->intended('/papeletas');
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            AuditLog::log(
                $user->id,
                'Logout',
                'Usuario cerró sesión del sistema',
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
