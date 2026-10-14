<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProfileCompletionController extends Controller
{
    /**
     * Paso 2 del primer acceso: registro de los datos de contacto faltantes
     * (correo y celular).
     */
    public function show()
    {
        $user = Auth::user();

        if (!$user->must_complete_profile) {
            return redirect($this->homeSegunRol($user));
        }

        $person = $user->person;
        $requiereEmail    = blank($person?->email);
        $requiereTelefono = blank($person?->telefono);

        // Si ya no falta nada, no hay nada que completar.
        if (!$requiereEmail && !$requiereTelefono) {
            $user->update(['must_complete_profile' => false]);

            return redirect($this->homeSegunRol($user));
        }

        return Inertia::render('Auth/CompleteProfile', [
            'nombre'   => $user->full_name,
            'dni'      => $user->dni,
            'email'    => $person?->email,
            'telefono' => $person?->telefono,
            'requiere_email'    => $requiereEmail,
            'requiere_telefono' => $requiereTelefono,
        ]);
    }

    /**
     * Guarda los datos de contacto faltantes y finaliza el primer acceso.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user->must_complete_profile) {
            return redirect($this->homeSegunRol($user));
        }

        $person = $user->person;
        $requiereEmail    = blank($person?->email);
        $requiereTelefono = blank($person?->telefono);

        $rules = [];
        $messages = [];

        if ($requiereEmail) {
            $rules['email'] = ['required', 'email', 'max:150'];
            $rules['email_confirmation'] = ['required', 'same:email'];
            $messages += [
                'email.required'     => 'El correo electrónico es obligatorio.',
                'email.email'        => 'Ingrese un correo electrónico válido.',
                'email_confirmation.required' => 'Debe confirmar su correo electrónico.',
                'email_confirmation.same'     => 'Los correos electrónicos no coinciden.',
            ];
        }

        if ($requiereTelefono) {
            $rules['telefono'] = ['required', 'string', 'regex:/^\d{9}$/'];
            $messages += [
                'telefono.required' => 'El número de celular es obligatorio.',
                'telefono.regex'    => 'El celular debe tener 9 dígitos (solo números).',
            ];
        }

        $validated = $request->validate($rules, $messages);

        $personUpdate = [];

        if ($requiereEmail) {
            // Evitar correos duplicados en la identidad (people).
            $emailOcupado = Person::where('email', $validated['email'])
                ->when($user->person_id, fn ($q) => $q->where('id', '!=', $user->person_id))
                ->exists();

            if ($emailOcupado) {
                throw ValidationException::withMessages([
                    'email' => 'Este correo electrónico ya está registrado.',
                ]);
            }

            $personUpdate['email'] = $validated['email'];
        }

        if ($requiereTelefono) {
            $personUpdate['telefono'] = $validated['telefono'];
        }

        if ($personUpdate) {
            $person->update($personUpdate);
        }

        $user->update(['must_complete_profile' => false]);

        AuditLog::log($user->id, 'Datos completados', 'Usuario completó sus datos de contacto');

        return redirect($this->homeSegunRol($user))
            ->with('success', 'Datos actualizados correctamente. ¡Bienvenido!');
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
