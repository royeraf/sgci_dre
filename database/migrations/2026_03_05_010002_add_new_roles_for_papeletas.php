<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\CustomRole;

return new class extends Migration
{
    public function up(): void
    {
        CustomRole::updateOrCreate(
            ['rol_id' => 'ROL011'],
            [
                'codigo' => 'jefe_inmediato',
                'nombre' => 'Jefe Inmediato',
                'descripcion' => 'Aprobación de papeletas de salida de su área',
                'nivel_acceso' => 6,
                'permisos_json' => [
                    'papeletas' => ['leer', 'aprobar'],
                    'personal' => ['leer'],
                ],
            ]
        );

        CustomRole::updateOrCreate(
            ['rol_id' => 'ROL012'],
            [
                'codigo' => 'empleado_portal',
                'nombre' => 'Empleado Portal',
                'descripcion' => 'Acceso al portal de empleados para solicitar papeletas',
                'nivel_acceso' => 1,
                'permisos_json' => [
                    'papeletas' => ['crear', 'leer'],
                ],
            ]
        );
    }

    public function down(): void
    {
        CustomRole::where('rol_id', 'ROL011')->delete();
        CustomRole::where('rol_id', 'ROL012')->delete();
    }
};
