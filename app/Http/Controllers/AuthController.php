<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

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
            'dni' => ['required', 'string', 'size:8', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string'],
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.regex' => 'El DNI solo debe contener números.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // Find user by DNI
        $user = User::where('dni', $request->dni)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => 'Las credenciales proporcionadas son incorrectas.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'credentials' => 'Su cuenta está desactivada. Contacte al administrador.',
            ]);
        }

        // Login the user
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
