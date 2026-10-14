<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pagina_de_recuperacion_disponible(): void
    {
        $this->get('/recuperar-contrasena')->assertStatus(200);
    }

    public function test_envia_nueva_contrasena_y_obliga_a_cambiarla(): void
    {
        Mail::fake();

        $email = 'reset.' . uniqid() . '@drehuanuco.gob.pe';
        [$user, $dni] = $this->crearUsuario(['email' => $email]);

        $response = $this->from('/recuperar-contrasena')->post('/recuperar-contrasena', [
            'identifier' => $dni,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        $enviado = null;
        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($email, &$enviado) {
            $enviado = $mail;
            return $mail->hasTo($email);
        });

        $user->refresh();
        $this->assertTrue($user->must_change_password);

        // Al ingresar con la contraseña temporal, el sistema obliga a cambiarla.
        $login = $this->post('/login', [
            'dni' => $dni,
            'password' => $enviado->password,
        ]);

        $login->assertRedirect('/primer-acceso');
    }

    public function test_identificador_inexistente_no_revela_ni_envia(): void
    {
        Mail::fake();

        $response = $this->from('/recuperar-contrasena')->post('/recuperar-contrasena', [
            'identifier' => 'noexiste.' . uniqid(),
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');
        Mail::assertNothingSent();
    }

    public function test_usuario_sin_correo_devuelve_error(): void
    {
        Mail::fake();

        [$user, $dni] = $this->crearUsuario(['email' => null]);

        $response = $this->from('/recuperar-contrasena')->post('/recuperar-contrasena', [
            'identifier' => $dni,
        ]);

        $response->assertSessionHasErrors('identifier');
        Mail::assertNothingSent();
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_identificador_es_obligatorio(): void
    {
        $response = $this->from('/recuperar-contrasena')->post('/recuperar-contrasena', [
            'identifier' => '',
        ]);

        $response->assertSessionHasErrors('identifier');
    }

    public function test_encuentra_usuario_por_dni_actualizado_en_people(): void
    {
        Mail::fake();

        $email = 'people.' . uniqid() . '@drehuanuco.gob.pe';
        $dni = $this->dniLibre();

        $person = Person::create([
            'dni' => $dni,
            'nombres' => 'Dni',
            'apellidos' => 'Actualizado',
            'tipo' => 'INTERNO',
            'email' => $email,
        ]);

        // El DNI canónico está en people.
        $user = User::create([
            'person_id' => $person->id,
            'username' => 'legacy' . uniqid(),
            'password' => Hash::make('ClaveInicial123'),
            'rol_id' => 'ROL012',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->from('/recuperar-contrasena')->post('/recuperar-contrasena', [
            'identifier' => $dni,
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(PasswordResetMail::class, fn ($mail) => $mail->hasTo($email));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_auto_provisiona_empleado_sin_cuenta_en_recuperacion(): void
    {
        Mail::fake();

        $email = 'nuevo.' . uniqid() . '@drehuanuco.gob.pe';
        $dni = $this->dniLibre();

        $person = Person::create([
            'dni' => $dni,
            'nombres' => 'Empleado',
            'apellidos' => 'Sin Cuenta',
            'tipo' => 'INTERNO',
            'email' => $email,
        ]);
        Employee::create(['person_id' => $person->id, 'estado' => 'ACTIVO']);

        $response = $this->from('/recuperar-contrasena')->post('/recuperar-contrasena', [
            'identifier' => $dni,
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(PasswordResetMail::class, fn ($mail) => $mail->hasTo($email));

        $user = User::where('person_id', $person->id)->first();
        $this->assertNotNull($user);
        $this->assertSame('ROL012', $user->rol_id);
        $this->assertTrue($user->must_change_password);
    }

    /**
     * Crea una persona interna, su usuario y devuelve [User, dni].
     */
    private function crearUsuario(array $personOverrides = []): array
    {
        $dni = $this->dniLibre();

        $person = Person::create(array_merge([
            'dni' => $dni,
            'nombres' => 'Recupera',
            'apellidos' => 'Clave',
            'tipo' => 'INTERNO',
            'email' => 'base.' . uniqid() . '@drehuanuco.gob.pe',
        ], $personOverrides));

        $user = User::create([
            'person_id' => $person->id,
            'username' => $dni,
            'password' => Hash::make('ClaveInicial123'),
            'rol_id' => 'ROL012',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        return [$user, $dni];
    }

    private function dniLibre(): string
    {
        foreach (range(1, 50) as $intento) {
            $dni = (string) random_int(46000000, 46999999);

            if (!Person::where('dni', $dni)->exists()
                && !User::where('username', $dni)->exists()) {
                return $dni;
            }
        }

        $this->fail('No se encontró un DNI libre para la prueba');
    }
}
