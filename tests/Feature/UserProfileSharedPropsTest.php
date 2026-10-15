<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserProfileSharedPropsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * El modal "Perfil de Usuario" lee sus datos de auth.user; deben incluir
     * todos los datos básicos (username, teléfono, título, cargo, área).
     */
    public function test_auth_user_comparte_los_datos_basicos_del_perfil(): void
    {
        $dni = $this->dniLibre();

        $person = Person::create([
            'dni'       => $dni,
            'nombres'   => 'Juan',
            'apellidos' => 'Pérez García',
            'email'     => 'juan.perez@drehuanuco.gob.pe',
            'telefono'  => '987654321',
            'titulo'    => 'Ing.',
            'tipo'      => 'INTERNO',
        ]);

        Employee::create([
            'person_id' => $person->id,
            'estado'    => 'ACTIVO',
        ]);

        $user = User::create([
            'person_id'            => $person->id,
            'username'             => $dni,
            'password'             => Hash::make('ClaveSegura123'),
            'cargo'                => 'Especialista Administrativo',
            'area'                 => 'Oficina de Informática',
            'rol_id'               => 'ROL012',
            'is_active'            => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.username', $dni)
                ->where('auth.user.dni', $dni)
                ->where('auth.user.name', 'Juan')
                ->where('auth.user.apellidos', 'Pérez García')
                ->where('auth.user.email', 'juan.perez@drehuanuco.gob.pe')
                ->where('auth.user.telefono', '987654321')
                ->where('auth.user.titulo', 'Ing.')
                ->where('auth.user.cargo', 'Especialista Administrativo')
                ->where('auth.user.area', 'Oficina de Informática')
            );
    }

    /**
     * DNI de 8 dígitos libre en people y users (columnas UNIQUE).
     */
    private function dniLibre(): string
    {
        foreach (range(1, 50) as $intento) {
            $dni = (string) random_int(40000000, 49999999);

            if (!Person::where('dni', $dni)->exists()
                && !User::where('username', $dni)->exists()) {
                return $dni;
            }
        }

        $this->fail('No se encontró un DNI libre para la prueba');
    }
}
