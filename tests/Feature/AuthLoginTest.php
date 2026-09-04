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

    public function test_user_can_login_with_username(): void
    {
        $user = User::where('dni', '12345678')->first();

        $response = $this->post('/login', [
            'dni' => $user->username,
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::where('dni', '12345678')->first();

        $response = $this->post('/login', [
            'dni' => $user->email,
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
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

    public function test_portal_employee_redirects_to_portal_papeletas(): void
    {
        $employeeUser = User::where('rol_id', 'ROL012')->first();
        if ($employeeUser) {
            $response = $this->actingAs($employeeUser)->get('/');
            $response->assertRedirect('/portal/papeletas');
        }
    }
}
