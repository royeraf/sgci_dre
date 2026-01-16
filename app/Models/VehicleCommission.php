<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VehicleCommission extends Model
{
    use HasUuids;

    protected $fillable = [
        'dependencia',
        'dia',
        'hora',
        'lugar',
        'motivo',
        'usuarios',
        'vehicle_id',
        'chofer',
        'hora_salida',
        'hora_regreso',
        'estado',
    ];

    protected $casts = [
        'dia' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the status display class
     */
    public function getStatusClassAttribute(): string
    {
        return match($this->estado) {
            'EN_COMISION' => 'bg-blue-100 text-blue-800',
            'COMPLETADA' => 'bg-green-100 text-green-800',
            'CANCELADA' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
