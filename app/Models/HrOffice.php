<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrOffice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_offices';

    protected $fillable = [
        'direction_id',
        'codigo',
        'nombre',
        'ubicacion',
        'piso',
        'telefono_interno',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con la dirección
     */
    public function direction(): BelongsTo
    {
        return $this->belongsTo(HrDirection::class, 'direction_id');
    }

    /**
     * Relación con empleados asignados a esta oficina
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'office_id');
    }

    /**
     * Scope para oficinas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener nombre completo con dirección
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->direction?->nombre} - {$this->nombre}";
    }
}
