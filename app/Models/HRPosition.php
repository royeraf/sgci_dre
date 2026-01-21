<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HRPosition extends Model
{
    use HasUuids;

    protected $table = 'hr_positions';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con empleados que tienen este cargo
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    /**
     * Scope para posiciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
