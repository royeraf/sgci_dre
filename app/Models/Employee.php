<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'genero',
        'direccion',
        'telefono',
        'correo',
        'cargo',
        'area',
        'fecha_ingreso',
        'tipo_contrato',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
    ];

    /**
     * Relación con vacaciones
     */
    public function vacations()
    {
        return $this->hasMany(Vacation::class, 'empleado_id');
    }

    /**
     * Nombre completo del empleado
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
