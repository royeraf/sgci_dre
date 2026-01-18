<?php

namespace Database\Seeders;

use App\Models\CustomRole;
use App\Models\User;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->seedUsers();
        $this->seedStaff();
    }

    private function seedRoles(): void
    {
        $roles = [
            [
                'rol_id' => 'ROL001',
                'codigo' => 'admin',
                'nombre' => 'Administrador del Sistema',
                'descripcion' => 'Acceso completo a todas las funcionalidades del sistema',
                'nivel_acceso' => 10,
                'permisos_json' => [
                    'usuarios' => ['crear', 'leer', 'editar', 'eliminar'],
                    'roles' => ['crear', 'leer', 'editar', 'eliminar'],
                    'personal' => ['crear', 'leer', 'editar', 'eliminar'],
                    'ocurrencias' => ['crear', 'leer', 'editar', 'eliminar', 'aprobar'],
                    'visitas' => ['crear', 'leer', 'editar', 'eliminar'],
                    'vehiculos' => ['crear', 'leer', 'editar', 'eliminar'],
                    'patrimonio' => ['crear', 'leer', 'editar', 'eliminar'],
                    'licencias' => ['crear', 'leer', 'editar', 'eliminar', 'aprobar'],
                    'reportes' => ['ver', 'exportar'],
                    'configuracion' => ['leer', 'editar'],
                ],
            ],
            [
                'rol_id' => 'ROL006',
                'codigo' => 'coordinador_vigilante',
                'nombre' => 'Coordinador de Vigilancia',
                'descripcion' => 'Supervisión del equipo de vigilancia y gestión de turnos',
                'nivel_acceso' => 5,
                'permisos_json' => [
                    'ocurrencias' => ['crear', 'leer', 'editar'],
                    'visitas' => ['crear', 'leer', 'editar'],
                    'vehiculos' => ['leer'],
                    'personal' => ['leer'],
                    'reportes' => ['ver'],
                ],
            ],
            [
                'rol_id' => 'ROL007',
                'codigo' => 'vigilante',
                'nombre' => 'Vigilante',
                'descripcion' => 'Acceso a libro de ocurrencias, control de personal, visitas externas y control vehicular',
                'nivel_acceso' => 3,
                'permisos_json' => [
                    'ocurrencias' => ['crear', 'leer'],
                    'personal' => ['leer'],
                    'visitas' => ['crear', 'leer', 'editar'],
                    'vehiculos' => ['crear', 'leer'],
                ],
            ],
            [
                'rol_id' => 'ROL008',
                'codigo' => 'jefe_bienestar',
                'nombre' => 'Jefe de Bienestar Social',
                'descripcion' => 'Gestión de bienestar del personal (Licencias)',
                'nivel_acceso' => 7,
                'permisos_json' => [
                    'personal' => ['leer'],
                    'licencias' => ['crear', 'leer', 'editar', 'eliminar', 'aprobar'],
                    'reportes' => ['ver'],
                ],
            ],
            [
                'rol_id' => 'ROL009',
                'codigo' => 'jefe_rrhh',
                'nombre' => 'Jefe de Recursos Humanos',
                'descripcion' => 'Gestión completa de recursos humanos, empleados, vacaciones y áreas',
                'nivel_acceso' => 8,
                'permisos_json' => [
                    'personal' => ['crear', 'leer', 'editar', 'eliminar'],
                    'recursos_humanos' => ['crear', 'leer', 'editar', 'eliminar'],
                    'vacaciones' => ['crear', 'leer', 'editar', 'eliminar', 'aprobar'],
                    'areas' => ['crear', 'leer', 'editar', 'eliminar'],
                    'cargos' => ['crear', 'leer', 'editar', 'eliminar'],
                    'licencias' => ['leer', 'aprobar'],
                    'reportes' => ['ver', 'exportar'],
                ],
            ],
            [
                'rol_id' => 'ROL010',
                'codigo' => 'gestor_citas',
                'nombre' => 'Gestor de Citas',
                'descripcion' => 'Gestión y administración de citas públicas',
                'nivel_acceso' => 6,
                'permisos_json' => [
                    'citas' => ['crear', 'leer', 'editar', 'eliminar', 'aprobar'],
                    'personal' => ['leer'],
                    'reportes' => ['ver'],
                ],
            ],
        ];

        foreach ($roles as $role) {
            CustomRole::updateOrCreate(
                ['rol_id' => $role['rol_id']],
                $role
            );
        }

        $this->command->info('✓ Roles created successfully');
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'dni' => '12345678',
                'name' => 'Administrador',
                'apellidos' => 'Sistema',
                'email' => 'admin@dre.gob.pe',
                'password' => Hash::make('admin123'),
                'rol_id' => 'ROL001',
                'cargo' => 'Administrador del Sistema',
                'area' => 'TI',
                'is_active' => true,
            ],
            [
                'dni' => '12345671',
                'name' => 'Juan Carlos',
                'apellidos' => 'Pérez García',
                'email' => 'jperez@dre.gob.pe',
                'password' => Hash::make('vigilante123'),
                'rol_id' => 'ROL007',
                'cargo' => 'Vigilante',
                'area' => 'Seguridad',
                'is_active' => true,
            ],
            [
                'dni' => '12345674',
                'name' => 'Fernando José',
                'apellidos' => 'Ramírez Castro',
                'email' => 'framirez@dre.gob.pe',
                'password' => Hash::make('coordinador123'),
                'rol_id' => 'ROL006',
                'cargo' => 'Coordinador de Vigilancia',
                'area' => 'Seguridad',
                'is_active' => true,
            ],
            [
                'dni' => '12345672',
                'name' => 'Ana Sofía',
                'apellidos' => 'Mendoza Portillo',
                'email' => 'asofia@dre.gob.pe',
                'password' => Hash::make('bienestar123'),
                'rol_id' => 'ROL008',
                'cargo' => 'Jefe de Bienestar Social',
                'area' => 'Bienestar Social',
                'is_active' => true,
            ],
            [
                'dni' => '12345673',
                'name' => 'María Elena',
                'apellidos' => 'Vargas Rojas',
                'email' => 'mvargas@dre.gob.pe',
                'password' => Hash::make('rrhh123'),
                'rol_id' => 'ROL009',
                'cargo' => 'Jefe de Recursos Humanos',
                'area' => 'Recursos Humanos',
                'is_active' => true,
            ],
            [
                'dni' => '12345675',
                'name' => 'Rosa María',
                'apellidos' => 'Torres Vega',
                'email' => 'rtorres@dre.gob.pe',
                'password' => Hash::make('citas123'),
                'rol_id' => 'ROL010',
                'cargo' => 'Gestor de Citas',
                'area' => 'Secretaría',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['dni' => $user['dni']],
                $user
            );
        }

        $this->command->info('✓ Users created successfully');
    }

    private function seedStaff(): void
    {
        $staffMembers = [
            [
                'dni' => '45678901',
                'nombres' => 'María Elena',
                'apellidos' => 'Vargas Rojas',
                'cargo' => 'Jefe de Recursos Humanos',
                'area' => 'Recursos Humanos',
                'regimen' => 'Nombrado',
                'telefono' => '999333444',
                'email' => 'mvargas@dre.gob.pe',
            ],
            [
                'dni' => '45678902',
                'nombres' => 'Carlos Eduardo',
                'apellidos' => 'Vega Salazar',
                'cargo' => 'Subdirector',
                'area' => 'Dirección',
                'regimen' => 'Nombrado',
                'telefono' => '999222333',
                'email' => 'cvega@dre.gob.pe',
            ],
            [
                'dni' => '45678903',
                'nombres' => 'Patricia Luz',
                'apellidos' => 'Fernández Quispe',
                'cargo' => 'Secretaria Ejecutiva',
                'area' => 'Dirección',
                'regimen' => 'CAS',
                'telefono' => '999888999',
                'email' => 'pfernandez@dre.gob.pe',
            ],
        ];

        foreach ($staffMembers as $staff) {
            Staff::updateOrCreate(
                ['dni' => $staff['dni']],
                $staff
            );
        }

        $this->command->info('✓ Staff members created successfully');
    }
}
