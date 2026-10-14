<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ForgotPasswordController extends Controller
{
    /**
     * Muestra el formulario para solicitar una nueva contraseña.
     */
    public function show()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Genera una contraseña temporal, la envía al correo registrado y obliga a
     * cambiarla en el siguiente inicio de sesión.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:150'],
        ], [
            'identifier.required' => 'Ingrese su DNI, usuario o correo electrónico.',
        ]);

        $identifier = trim($validated['identifier']);
        $throttleKey = 'password-reset|' . Str::lower($identifier) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            throw ValidationException::withMessages([
                'identifier' => 'Demasiadas solicitudes. Intente nuevamente en ' . $minutes . ' '
                    . ($minutes === 1 ? 'minuto' : 'minutos') . '.',
            ]);
        }

        $user = User::findByIdentifier($identifier);

        // Si aún no tiene cuenta, pero es personal activo, se aprovisiona aquí
        // para que pueda recuperar el acceso sin haber ingresado antes.
        if (!$user) {
            $user = $this->provisionarEmpleado($identifier);
        }

        // No revelar si la cuenta existe: mensaje genérico cuando no se encuentra.
        if (!$user) {
            RateLimiter::hit($throttleKey, 300);

            return redirect()->route('login')->with('success', $this->mensajeGenerico());
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => 'Su cuenta está desactivada. Contacte al administrador.',
            ]);
        }

        // El correo viene de people (usuario vinculado a RRHH) o del propio users.
        $email = $user->email;

        if (!$email) {
            throw ValidationException::withMessages([
                'identifier' => 'No tiene un correo registrado. Contacte al administrador para actualizar sus datos.',
            ]);
        }

        $password = Str::password(10, symbols: false, spaces: false);

        $user->update([
            'password' => Hash::make($password),
            // Deberá cambiarla en su primer ingreso con esta contraseña temporal.
            'must_change_password' => true,
            'password_changed_at'  => null,
        ]);

        Mail::to($email)->send(new PasswordResetMail($user->full_name, $password));

        RateLimiter::hit($throttleKey, 300);

        AuditLog::log(
            $user->id,
            'Recuperación de contraseña',
            'Se envió una nueva contraseña temporal al correo registrado',
        );

        return redirect()->route('login')->with('success', $this->mensajeGenerico());
    }

    private function mensajeGenerico(): string
    {
        return 'Correo enviado. Si sus datos están registrados, recibirá una nueva contraseña en su correo '
            . 'electrónico. Ingrésela y el sistema le pedirá cambiarla.';
    }

    /**
     * Aprovisiona la cuenta de un empleado activo que aún no tiene usuario,
     * replicando el criterio del login (rol ROL012). Devuelve null si el
     * identificador no corresponde a un empleado activo.
     */
    private function provisionarEmpleado(string $identifier): ?User
    {
        if (!preg_match('/^[0-9]{8}$/', $identifier)) {
            return null;
        }

        $employee = Employee::activos()
            ->whereHas('person', fn ($q) => $q->where('dni', $identifier))
            ->first();

        if (!$employee) {
            return null;
        }

        // La persona puede ya tener cuenta con otro identificador (users.person_id es UNIQUE).
        $user = User::where('person_id', $employee->person_id)->first();

        if ($user) {
            return $user;
        }

        try {
            return User::create([
                'person_id' => $employee->person_id,
                'username'  => $identifier,
                'password'  => Hash::make(Str::random(40)),
                'rol_id'    => 'ROL012',
                'is_active' => true,
                'must_change_password' => true,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return User::where('person_id', $employee->person_id)->first();
        }
    }
}
