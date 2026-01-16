<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VehicleMaintenance extends Model
{
    use HasUuids;

    protected $fillable = [
        'vehicle_id',
        'fecha',
        'factura',
        'kilometraje',
        'orden_sc',
        'proveedor',
        'detalle',
        'costo',
        'vigilante',
        'responsable',
    ];

    protected $casts = [
        'fecha' => 'date',
        'costo' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
