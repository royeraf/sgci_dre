<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Vehicle extends Model
{
    use HasUuids;

    protected $fillable = [
        'tipo',
        'marca',
        'modelo',
        'placa',
        'anio',
        'motor',
        'chasis',
        'cilindros',
        'asientos',
        'color',
        'estado',
        'kilometraje',
        'combustible',
        'fecha_soat',
        'fecha_revision',
        'observaciones',
    ];

    protected $casts = [
        'fecha_soat' => 'date',
        'fecha_revision' => 'date',
    ];

    public function commissions()
    {
        return $this->hasMany(VehicleCommission::class);
    }

    public function maintenances()
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function serviceRequirements()
    {
        return $this->hasMany(VehicleServiceRequirement::class);
    }

    /**
     * Get display name (placa - marca modelo)
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->placa} - {$this->marca} {$this->modelo}";
    }
}
