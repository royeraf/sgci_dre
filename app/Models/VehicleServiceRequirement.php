<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VehicleServiceRequirement extends Model
{
    use HasUuids;

    protected $fillable = [
        'conductor',
        'vehicle_id',
        'estado_vehiculo',
        'estado_motor',
        'encendido_electrico',
        'transmision',
        'pintura_carroceria',
        'llantas',
        'herramientas',
        'implementos_aseo',
        'comisiones_realizadas',
        'motivo',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
