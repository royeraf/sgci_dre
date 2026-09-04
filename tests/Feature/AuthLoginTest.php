<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
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

    public function test_dni_must_be_exactly_8_digits(): void
    {
        // 7 digits
        $response1 = $this->from('/login')->post('/login', [
            'dni' => '1234567',
            'password' => 'admin123',
        ]);
        $response1->assertSessionHasErrors('dni');

        // Letters
        $response2 = $this->from('/login')->post('/login', [
            'dni' => 'abcdefgh',
            'password' => 'admin123',
        ]);
        $response2->assertSessionHasErrors('dni');

        // 9 digits
        $response3 = $this->from('/login')->post('/login', [
            'dni' => '123456789',
            'password' => 'admin123',
        ]);
        $response3->assertSessionHasErrors('dni');
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
}
