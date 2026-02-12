<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrDirection extends Model
{
    use HasUuids;

    protected $table = 'hr_directions';

    protected $fillable = [
        'nombre',
        'abreviacion',
        'codigo',
        'descripcion',
        'telefono_interno',
        'ubicacion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con oficinas de la dirección
     */
    public function offices(): HasMany
    {
        return $this->hasMany(HrOffice::class, 'direction_id');
    }

    /**
     * Relación con empleados de la dirección
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'direction_id');
    }

    /**
     * Scope para direcciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
