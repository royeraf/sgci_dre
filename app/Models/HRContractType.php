<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HRContractType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_contract_types';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con empleados
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'contract_type_id');
    }

    /**
     * Scope para tipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
