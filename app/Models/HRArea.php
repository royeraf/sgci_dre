<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HRArea extends Model
{
    use HasUuids;

    protected $table = 'hr_areas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con oficinas del área
     */
    public function offices(): HasMany
    {
        return $this->hasMany(HrOffice::class, 'area_id');
    }

    /**
     * Relación con empleados del área
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'area_id');
    }

    /**
     * Scope para áreas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
