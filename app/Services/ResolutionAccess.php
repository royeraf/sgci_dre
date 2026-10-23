<?php

namespace App\Services;

use App\Models\User;

/** Action permissions are confined to Resoluciones; existing access stays compatible. */
class ResolutionAccess
{
    public function allows(User $user, string $action = 'ver'): bool
    {
        if ($user->rol_id === 'ROL001') return true;
        $modules = $user->modulos_json ?? [];
        if ($modules && !in_array('resoluciones', $modules, true)) return false;
        $permissions = $user->customRole?->permisos_json ?? [];
        if (array_key_exists('resoluciones', $permissions)) {
            return is_array($permissions['resoluciones']) && in_array($action, $permissions['resoluciones'], true);
        }
        return in_array('resoluciones', $modules, true);
    }

    public function forUser(User $user): array
    {
        return array_combine(['ver', 'subir', 'editar'], array_map(fn($action) => $this->allows($user, $action), ['ver', 'subir', 'editar']));
    }

    public function roleData(array $data, ?array $existing = null): array
    {
        if (!array_key_exists('resolution_access', $data)) return $data;
        $permissions = $existing ?? [];
        if ($data['resolution_access'] === null) unset($permissions['resoluciones']);
        else {
            $actions = array_values(array_unique($data['resolution_access']));
            if ($actions && !in_array('ver', $actions, true)) $actions[] = 'ver';
            $permissions['resoluciones'] = $actions;
        }
        unset($data['resolution_access']);
        $data['permisos_json'] = $permissions ?: null;
        return $data;
    }
}
