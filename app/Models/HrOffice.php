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
        'area_id',
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
     * Relación con el área
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(HRArea::class, 'area_id');
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
     * Obtener nombre completo con área
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->area?->nombre} - {$this->nombre}";
    }
}
