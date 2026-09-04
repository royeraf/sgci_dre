<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            'dni.required' => 'El DNI, usuario o correo es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $identifier = trim($request->input('dni'));
        $throttleKey = Str::transliterate(Str::lower($identifier) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'credentials' => "Demasiados intentos fallidos. Por favor espere {$seconds} segundos antes de volver a intentar.",
            ]);
        }

        // Find user by DNI, username, or email
        $user = User::where('dni', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
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

        if ($user->rol_id === 'ROL012') {
            return redirect()->intended('/portal/papeletas');
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
