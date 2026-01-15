<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomRole extends Model
{
    use HasFactory;

    protected $primaryKey = 'rol_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rol_id',
        'codigo',
        'nombre',
        'descripcion',
        'nivel_acceso',
        'permisos_json',
        'activo',
    ];

    protected $casts = [
        'permisos_json' => 'array',
        'activo' => 'boolean',
        'nivel_acceso' => 'integer',
    ];

    /**
     * Get the users with this role.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'rol_id', 'rol_id');
    }

    /**
     * Scope to get only active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Check if role has permission for a module action.
     */
    public function hasPermission(string $module, string $action): bool
    {
        if (!$this->permisos_json || !isset($this->permisos_json[$module])) {
            return false;
        }

        return in_array($action, $this->permisos_json[$module]);
    }
}
