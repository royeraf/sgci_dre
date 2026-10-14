<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FirstAccessController extends Controller
{
    /**
     * Paso 1 del primer acceso: cambio obligatorio de contraseña.
     */
    public function show()
    {
        $user = Auth::user();
        $person = $user->person;

        return Inertia::render('Auth/FirstAccess', [
            'nombre' => $user->full_name,
            'dni'    => $user->dni,
            // El paso 2 solo aplica si faltan AMBOS datos (correo y celular).
            'datos_pendientes' => blank($person?->email) && blank($person?->telefono),
        ]);
    }

    /**
     * Cambia la contraseña. Si faltan datos de contacto, deriva al paso 2.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required'  => 'La nueva contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // No permitir reutilizar la contraseña actual (inicial = DNI).
        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña debe ser diferente a la actual.',
            ]);
        }

        // Solo se pide completar datos si faltan AMBOS (correo y celular).
        $datosFaltantes = blank($user->person?->email) && blank($user->person?->telefono);

        $user->password             = Hash::make($validated['password']);
        $user->must_change_password = false;
        $user->must_complete_profile = $datosFaltantes;
        $user->password_changed_at   = now();
        $user->save();

        AuditLog::log($user->id, 'Primer acceso', 'Usuario cambió su contraseña inicial');

        // Paso 2: completar datos faltantes.
        if ($datosFaltantes) {
            return redirect()->route('profile.complete')
                ->with('success', 'Contraseña actualizada. Ahora complete sus datos de contacto.');
        }

        return redirect($this->homeSegunRol($user))
            ->with('success', 'Contraseña actualizada. ¡Bienvenido!');
    }

    /**
     * Ruta de inicio según el rol del usuario.
     */
    private function homeSegunRol(User $user): string
    {
        return match ($user->rol_id) {
            'ROL008' => '/bienestar',
            'ROL009' => '/hr',
            'ROL010' => '/citas',
            'ROL011' => '/papeletas',
            default  => '/dashboard',
        };
    }
}
