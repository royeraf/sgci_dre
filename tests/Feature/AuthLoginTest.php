<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use DatabaseTransactions;
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_portal_login_page_renders_successfully(): void
    {
        $response = $this->get('/portal/login');
        $response->assertStatus(200);
    }

    public function test_root_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_user_can_login_with_dni(): void
    {
        $user = User::where('dni', '12345678')->first();
        if (!$user) {
            $user = User::create([
                'dni' => '12345678',
                'name' => 'Admin Test',
                'username' => 'admintest',
                'email' => 'admintest@dre.gob.pe',
                'password' => Hash::make('admin123'),
                'rol_id' => 'ROL001',
                'is_active' => true,
            ]);
        }

        $response = $this->post('/login', [
            'dni' => '12345678',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username(): void
    {
        $response = $this->post('/login', [
            'dni' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_empty_fields_fail_validation(): void
    {
        $response = $this->from('/login')->post('/login', [
            'dni' => '',
            'password' => '',
        ]);
        $response->assertSessionHasErrors(['dni', 'password']);
    }

    public function test_invalid_credentials_returns_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'dni' => '12345678',
            'password' => 'wrongpassword123',
        ]);

        $response->assertSessionHasErrors('credentials');
        $this->assertGuest();
    }

    public function test_employee_rol012_redirects_to_dashboard(): void
    {
        $employeeUser = User::where('rol_id', 'ROL012')->first();
        if ($employeeUser) {
            $response = $this->actingAs($employeeUser)->get('/');
            $response->assertRedirect('/dashboard');
        }
    }

    public function test_jefe_rol011_redirects_to_papeletas(): void
    {
        $jefeUser = User::where('rol_id', 'ROL011')->first();
        if ($jefeUser) {
            $response = $this->actingAs($jefeUser)->get('/');
            $response->assertRedirect('/papeletas');
        }
    }

    public function test_home_route_redirects_to_dashboard(): void
    {
        $response = $this->get('/home');
        $response->assertRedirect('/dashboard');
    }

    public function test_authenticated_user_accessing_login_redirects_to_dashboard(): void
    {
        $adminUser = User::where('rol_id', 'ROL001')->first();
        $response = $this->actingAs($adminUser)->get('/login');
        $response->assertRedirect('/dashboard');
    }

    public function test_csrf_middleware_excludes_login_and_logout(): void
    {
        $middleware = new \App\Http\Middleware\VerifyCsrfToken(app(), app('encrypter'));
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);

        $this->assertContains('login', $except);
        $this->assertContains('logout', $except);
        $this->assertContains('portal/login', $except);
        $this->assertContains('portal/logout', $except);
    }

    public function test_patch_on_login_does_not_throw_method_not_allowed(): void
    {
        $response = $this->patch('/login');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_json_request_to_users_password_returns_401(): void
    {
        $response = $this->patchJson('/users/1/password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }

    public function test_auto_provision_no_duplica_si_la_persona_ya_tiene_cuenta(): void
    {
        // Empleado activo cuya persona ya tiene cuenta con otro identificador:
        // entrar con DNI como usuario y clave no debe lanzar 500 por
        // users_person_id_unique, sino responder credenciales incorrectas.
        $empleado = Employee::where('estado', 'ACTIVO')
            ->whereHas('person')
            ->whereNotIn('person_id', User::whereNotNull('person_id')->select('person_id'))
            ->inRandomOrder()
            ->firstOrFail();

        $dni = $this->dniLibre();
        $empleado->person->update(['dni' => $dni]);

        $otro = $this->dniLibre();
        User::create([
            'person_id' => $empleado->person_id,
            'username' => $otro,
            'dni' => $otro,
            'password' => Hash::make('otraclave123'),
            'rol_id' => 'ROL012',
            'is_active' => true,
        ]);

        $response = $this->from('/login')->post('/login', [
            'dni' => $dni,
            'password' => $dni,
        ]);

        $response->assertSessionHasErrors('credentials');
        $this->assertGuest();
        $this->assertSame(1, User::where('person_id', $empleado->person_id)->count());
    }

    /** DNI de 8 dígitos libre en people y users (columnas UNIQUE). */
    private function dniLibre(): string
    {
        foreach (range(1, 50) as $intento) {
            $dni = (string) random_int(48000000, 48999999);

            if (!Person::where('dni', $dni)->exists()
                && !User::where('dni', $dni)->exists()
                && !User::where('username', $dni)->exists()) {
                return $dni;
            }
        }

        $this->fail('No se encontró un DNI libre para la prueba');
    }

    public function test_admin_can_update_user_password(): void
    {
        $admin = User::where('rol_id', 'ROL001')->first();
        $targetUser = User::where('id', '!=', $admin->id)->first();

        $response = $this->actingAs($admin)->patchJson("/users/{$targetUser->id}/password", [
            'password' => 'nuevopassword123',
            'password_confirmation' => 'nuevopassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Contraseña actualizada exitosamente']);
        $this->assertTrue(Hash::check('nuevopassword123', $targetUser->fresh()->password));
    }
}
