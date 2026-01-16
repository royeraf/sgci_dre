<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Vacation;
use App\Models\HRArea;
use App\Models\HRPosition;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear Áreas
        $areas = [
            ['nombre' => 'Administración', 'descripcion' => 'Oficina de Administración General'],
            ['nombre' => 'Dirección Regional', 'descripcion' => 'Despacho de la Dirección Regional'],
            ['nombre' => 'Gestión Pedagógica', 'descripcion' => 'Área de Gestión Pedagógica'],
            ['nombre' => 'Recursos Humanos', 'descripcion' => 'Oficina de Recursos Humanos'],
            ['nombre' => 'Informática', 'descripcion' => 'Oficina de Tecnologías de la Información'],
            ['nombre' => 'Contabilidad', 'descripcion' => 'Oficina de Contabilidad y Presupuesto'],
        ];

        foreach ($areas as $area) {
            HRArea::updateOrCreate(['nombre' => $area['nombre']], $area);
        }

        // 2. Crear Cargos
        $positions = [
            ['nombre' => 'Técnico Administrativo', 'descripcion' => 'Soporte técnico en oficina'],
            ['nombre' => 'Secretaria Ejecutiva', 'descripcion' => 'Gestión de agenda y documentos'],
            ['nombre' => 'Especialista en Educación', 'descripcion' => 'Asesoría pedagógica'],
            ['nombre' => 'Contador', 'descripcion' => 'Gestión financiera y contable'],
            ['nombre' => 'Asistente de RRHH', 'descripcion' => 'Soporte en gestión de personal'],
            ['nombre' => 'Especialista en Sistemas', 'descripcion' => 'Soporte informático'],
        ];

        foreach ($positions as $pos) {
            HRPosition::updateOrCreate(['nombre' => $pos['nombre']], $pos);
        }

        // 3. Crear empleados de ejemplo
        $employees = [
            [
                'dni' => '12345678',
                'nombres' => 'Juan Carlos',
                'apellidos' => 'García López',
                'fecha_nacimiento' => '1985-03-15',
                'genero' => 'Masculino',
                'direccion' => 'Jr. Huánuco 123',
                'telefono' => '962345678',
                'correo' => 'jgarcia@drehco.gob.pe',
                'cargo' => 'Técnico Administrativo',
                'area' => 'Administración',
                'fecha_ingreso' => '2015-03-01',
                'tipo_contrato' => 'Nombrado',
                'estado' => 'ACTIVO',
            ],
            [
                'dni' => '87654321',
                'nombres' => 'María Elena',
                'apellidos' => 'Torres Díaz',
                'fecha_nacimiento' => '1990-07-22',
                'genero' => 'Femenino',
                'direccion' => 'Av. Universitaria 456',
                'telefono' => '987654321',
                'correo' => 'mtorres@drehco.gob.pe',
                'cargo' => 'Secretaria Ejecutiva',
                'area' => 'Dirección Regional',
                'fecha_ingreso' => '2018-06-15',
                'tipo_contrato' => 'CAS',
                'estado' => 'ACTIVO',
            ],
            [
                'dni' => '11223344',
                'nombres' => 'Pedro Antonio',
                'apellidos' => 'Ramírez Mendoza',
                'fecha_nacimiento' => '1978-11-08',
                'genero' => 'Masculino',
                'direccion' => 'Jr. Dos de Mayo 789',
                'telefono' => '976543210',
                'correo' => 'pramirez@drehco.gob.pe',
                'cargo' => 'Especialista en Educación',
                'area' => 'Gestión Pedagógica',
                'fecha_ingreso' => '2010-02-20',
                'tipo_contrato' => 'Nombrado',
                'estado' => 'ACTIVO',
            ],
            [
                'dni' => '44332211',
                'nombres' => 'Ana Lucía',
                'apellidos' => 'Fernández Quispe',
                'fecha_nacimiento' => '1988-04-30',
                'genero' => 'Femenino',
                'direccion' => 'Jr. Leoncio Prado 321',
                'telefono' => '965432109',
                'correo' => 'afernandez@drehco.gob.pe',
                'cargo' => 'Contador',
                'area' => 'Contabilidad',
                'fecha_ingreso' => '2019-01-02',
                'tipo_contrato' => 'CAS',
                'estado' => 'ACTIVO',
            ],
            [
                'dni' => '55667788',
                'nombres' => 'Luis Miguel',
                'apellidos' => 'Espinoza Vargas',
                'fecha_nacimiento' => '1992-09-12',
                'genero' => 'Masculino',
                'direccion' => 'Av. Los Incas 654',
                'telefono' => '954321098',
                'correo' => 'lespinoza@drehco.gob.pe',
                'cargo' => 'Asistente de RRHH',
                'area' => 'Recursos Humanos',
                'fecha_ingreso' => '2020-03-16',
                'tipo_contrato' => 'CAS',
                'estado' => 'ACTIVO',
            ],
        ];

        $createdEmployees = [];
        foreach ($employees as $empData) {
            $createdEmployees[] = Employee::updateOrCreate(['dni' => $empData['dni']], $empData);
        }

        // 4. Crear vacaciones de ejemplo
        Vacation::firstOrCreate([
            'empleado_id' => $createdEmployees[0]->id,
            'dni' => $createdEmployees[0]->dni,
            'periodo' => '2026',
        ], [
            'fecha_inicio' => Carbon::now()->addDays(15)->toDateString(),
            'fecha_fin' => Carbon::now()->addDays(29)->toDateString(),
            'dias_tomados' => 15,
            'dias_pendientes' => 15,
            'estado' => 'PROGRAMADO',
            'observaciones' => 'Vacaciones programadas para febrero',
        ]);

        Vacation::firstOrCreate([
            'empleado_id' => $createdEmployees[2]->id,
            'dni' => $createdEmployees[2]->dni,
            'periodo' => '2025',
        ], [
            'fecha_inicio' => '2025-12-01',
            'fecha_fin' => '2025-12-30',
            'dias_tomados' => 30,
            'dias_pendientes' => 0,
            'estado' => 'COMPLETADO',
            'observaciones' => 'Vacaciones del periodo 2025',
        ]);

        $this->command->info('Seeder de RRHH (Áreas, Cargos, Empleados, Vacaciones) ejecutado correctamente.');
    }
}
