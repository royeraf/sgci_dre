<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FirstAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_pagina_de_primer_acceso_es_accesible_con_la_bandera_activa(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);

        $response = $this->actingAs($user)->get('/primer-acceso');

        $response->assertStatus(200);
    }

    public function test_login_auto_provisiona_empleado_y_redirige_a_primer_acceso(): void
    {
        $dni = $this->dniLibre();
        $person = Person::create([
            'dni' => $dni,
            'nombres' => 'Empleado',
            'apellidos' => 'Nuevo',
            'tipo' => 'INTERNO',
        ]);
        Employee::create([
            'person_id' => $person->id,
            'estado' => 'ACTIVO',
        ]);

        $response = $this->post('/login', [
            'dni' => $dni,
            'password' => $dni,
        ]);

        $response->assertRedirect('/primer-acceso');
        $this->assertAuthenticated();

        $user = User::where('person_id', $person->id)->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->must_change_password);
    }

    public function test_login_con_dni_actualizado_en_people(): void
    {
        $dni = $this->dniLibre();
        $person = Person::create([
            'dni' => $dni,
            'nombres' => 'Dni',
            'apellidos' => 'Cambiado',
            'tipo' => 'INTERNO',
        ]);
        Employee::create(['person_id' => $person->id, 'estado' => 'ACTIVO']);

        // El DNI canónico está en people.
        $user = User::create([
            'person_id' => $person->id,
            'username' => 'legacy' . uniqid(),
            'password' => Hash::make('ClaveInicial123'),
            'rol_id' => 'ROL012',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'dni' => $dni,
            'password' => 'ClaveInicial123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_usuario_con_bandera_es_redirigido_a_primer_acceso(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/primer-acceso');
    }

    public function test_usuario_puede_completar_primer_acceso_en_dos_pasos(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);
        $email = 'primer.acceso.' . uniqid() . '@drehuanuco.gob.pe';

        // Paso 1: solo contraseña -> deriva al paso 2 (faltan datos).
        $paso1 = $this->actingAs($user)->post('/primer-acceso', [
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ]);

        $paso1->assertRedirect('/completar-datos');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue($user->must_complete_profile);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('NuevaClave123', $user->password));

        // Mientras el perfil está pendiente, se fuerza el paso 2.
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/completar-datos');

        // Paso 2: datos faltantes.
        $paso2 = $this->actingAs($user)->post('/completar-datos', [
            'email' => $email,
            'email_confirmation' => $email,
            'telefono' => '987654321',
        ]);

        $paso2->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertFalse($user->must_complete_profile);
        $this->assertSame($email, $user->person->email);
        $this->assertSame('987654321', $user->person->telefono);
    }

    public function test_primer_acceso_solo_pide_password_si_los_datos_ya_existen(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);
        $email = 'existente.' . uniqid() . '@drehuanuco.gob.pe';
        $user->person->update(['email' => $email, 'telefono' => '999111222']);

        // El paso 1 no anuncia datos pendientes.
        $this->actingAs($user)->get('/primer-acceso')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/FirstAccess')
                ->where('datos_pendientes', false));

        $response = $this->actingAs($user)->post('/primer-acceso', [
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ]);

        // Como ya tiene datos, no pasa por el paso 2.
        $response->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertFalse($user->must_complete_profile);
        $this->assertSame($email, $user->person->email);
        $this->assertSame('999111222', $user->person->telefono);
    }

    public function test_pide_completar_datos_si_falta_solo_uno(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);
        $email = 'soloemail.' . uniqid() . '@drehuanuco.gob.pe';
        $user->person->update(['email' => $email]); // el celular sigue vacío

        // Al faltar uno, el paso 1 anuncia datos pendientes.
        $this->actingAs($user)->get('/primer-acceso')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/FirstAccess')
                ->where('datos_pendientes', true));

        // Paso 1 -> deriva al paso 2.
        $this->actingAs($user)->post('/primer-acceso', [
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect('/completar-datos');

        // El paso 2 solo pide el celular (el correo ya está).
        $this->actingAs($user->fresh())->get('/completar-datos')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/CompleteProfile')
                ->where('requiere_email', false)
                ->where('requiere_telefono', true));

        $response = $this->actingAs($user->fresh())->post('/completar-datos', [
            'telefono' => '987654321',
        ]);

        $response->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame($email, $user->person->email);
        $this->assertSame('987654321', $user->person->telefono);
        $this->assertFalse($user->must_complete_profile);
    }

    public function test_admin_no_es_obligado_a_completar_datos(): void
    {
        $admin = User::where('rol_id', 'ROL001')->first();
        if (!$admin) {
            $this->markTestSkipped('No hay usuario administrador en la base.');
        }

        // Aun sin datos de contacto, el admin navega con normalidad.
        $admin->person?->update(['email' => null, 'telefono' => null]);

        $this->actingAs($admin)->get('/api/server-time')->assertOk();
    }

    public function test_paso_2_exige_que_los_correos_coincidan(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);

        $this->actingAs($user)->post('/primer-acceso', [
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect('/completar-datos');

        $response = $this->actingAs($user->fresh())->from('/completar-datos')->post('/completar-datos', [
            'email' => 'correo.a@drehuanuco.gob.pe',
            'email_confirmation' => 'correo.b@drehuanuco.gob.pe',
            'telefono' => '987654321',
        ]);

        $response->assertSessionHasErrors('email_confirmation');
        $this->assertTrue($user->fresh()->must_complete_profile);
    }

    public function test_primer_acceso_valida_campos_obligatorios(): void
    {
        $user = $this->crearUsuarioEmpleado(['must_change_password' => true]);

        $response = $this->actingAs($user)->from('/primer-acceso')->post('/primer-acceso', [
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_primer_acceso_no_permite_reutilizar_la_contrasena_inicial(): void
    {
        $dni = $this->dniLibre();
        $person = Person::create([
            'dni' => $dni,
            'nombres' => 'Empleado',
            'apellidos' => 'Prueba',
            'tipo' => 'INTERNO',
        ]);
        $user = $this->crearUsuarioEmpleado([
            'must_change_password' => true,
            'person_id' => $person->id,
            'password' => Hash::make($dni),
        ]);

        $response = $this->actingAs($user)->from('/primer-acceso')->post('/primer-acceso', [
            'password' => $dni,
            'password_confirmation' => $dni,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->must_change_password);
    }

    /**
     * Crea un usuario vinculado a una persona y a un empleado activo.
     */
    private function crearUsuarioEmpleado(array $overrides = []): User
    {
        $person = isset($overrides['person_id'])
            ? Person::findOrFail($overrides['person_id'])
            : Person::create([
                'dni' => $this->dniLibre(),
                'nombres' => 'Usuario',
                'apellidos' => 'Prueba',
                'tipo' => 'INTERNO',
            ]);

        Employee::create([
            'person_id' => $person->id,
            'estado' => 'ACTIVO',
        ]);

        $dni = $person->dni;

        return User::create(array_merge([
            'person_id' => $person->id,
            'username' => $dni,
            'password' => Hash::make('ClaveInicial123'),
            'rol_id' => 'ROL012',
            'is_active' => true,
            'must_change_password' => true,
        ], $overrides));
    }

    /**
     * DNI de 8 dígitos libre en people y users (columnas UNIQUE).
     */
    private function dniLibre(): string
    {
        foreach (range(1, 50) as $intento) {
            $dni = (string) random_int(49000000, 49999999);

            if (!Person::where('dni', $dni)->exists()
                && !User::where('username', $dni)->exists()) {
                return $dni;
            }
        }

        $this->fail('No se encontró un DNI libre para la prueba');
    }
}
