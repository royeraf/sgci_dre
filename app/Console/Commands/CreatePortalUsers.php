<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreatePortalUsers extends Command
{
    protected $signature = 'portal:create-users {--role=ROL012 : Rol a asignar (ROL012=Empleado, ROL011=Jefe)} {--dry-run : Solo mostrar sin crear}';
    protected $description = 'Crear usuarios del portal para empleados activos (password inicial = DNI)';

    public function handle(): int
    {
        $role = $this->option('role');
        $dryRun = $this->option('dry-run');

        if (!in_array($role, ['ROL011', 'ROL012'])) {
            $this->error("Rol invalido. Use ROL011 o ROL012.");
            return 1;
        }

        $employees = Employee::activos()
            ->with('person')
            ->get()
            ->filter(fn ($e) => $e->person && $e->person->dni);

        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $dni = $employee->person->dni;
            // Verificar si ya existe un usuario para esta persona o DNI
            $existingByPerson = User::where('person_id', $employee->person_id)->first();
            $existingByDni    = User::where('dni', $dni)->first();

            if ($existingByPerson || $existingByDni) {
                $skipped++;
                if ($this->getOutput()->isVerbose()) {
                    $reason = $existingByPerson ? "persona ya vinculada (rol {$existingByPerson->rol_id})" : "DNI ya existe (rol {$existingByDni->rol_id})";
                    $this->line("  Omitido: {$dni} ({$employee->full_name}) - {$reason}");
                }
                continue;
            }

            if ($dryRun) {
                $this->line("  [DRY-RUN] Crearía: {$dni} - {$employee->full_name} ({$role})");
                $created++;
                continue;
            }

            // Username generado desde el DNI (que es la clave de login)
            $username = $dni;
            $counter  = 1;
            while (User::where('username', $username)->exists()) {
                $username = $dni . $counter;
                $counter++;
            }

            // Datos personales (nombre, email, tel) ya están en people a través de person_id
            // Solo guardamos person_id y dni (clave de login) en users
            User::create([
                'person_id' => $employee->person_id,
                'username'  => $username,
                'dni'       => $dni,
                'password'  => Hash::make($dni),
                'rol_id'    => $role,
                'is_active' => true,
            ]);

            $created++;
        }

        $this->info("Resultado: {$created} usuarios " . ($dryRun ? 'por crear' : 'creados') . ", {$skipped} omitidos (ya existían).");

        if (!$dryRun && $created > 0) {
            $this->warn("Los usuarios creados tienen como contraseña su DNI. Se recomienda solicitar cambio de contraseña.");
        }

        return 0;
    }
}
