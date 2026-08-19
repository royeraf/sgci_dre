<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\CustomRole;

return new class extends Migration
{
    /**
     * Roles que reciben la acción 'autorizar' sobre el módulo 'vehiculos':
     * el Coordinador de Vigilancia, que supervisa al equipo que hoy solicita
     * las salidas (ROL007) y es quien las autoriza.
     */
    private const ROLES = ['ROL006'];

    public function up(): void
    {
        foreach (self::ROLES as $rolId) {
            $role = CustomRole::find($rolId);
            if (!$role) {
                continue;
            }

            $permisos = $role->permisos_json ?? [];
            $acciones = $permisos['vehiculos'] ?? [];
            if (!in_array('autorizar', $acciones, true)) {
                $acciones[] = 'autorizar';
            }
            $permisos['vehiculos'] = $acciones;

            $role->update(['permisos_json' => $permisos]);
        }
    }

    public function down(): void
    {
        foreach (self::ROLES as $rolId) {
            $role = CustomRole::find($rolId);
            if (!$role) {
                continue;
            }

            $permisos = $role->permisos_json ?? [];
            if (isset($permisos['vehiculos'])) {
                $permisos['vehiculos'] = array_values(array_diff($permisos['vehiculos'], ['autorizar']));
            }

            $role->update(['permisos_json' => $permisos]);
        }
    }
};
