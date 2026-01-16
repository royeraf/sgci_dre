<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class VehicleHandover extends Model
{
    use HasUuids;

    protected $fillable = [
        'fecha',
        'entidad',
        'denominacion',
        'placa',
        'color',
        'kilometraje',
        'carroceria',
        'n_motor',
        'sistemas',
        'abastecimiento',
        'recepciona',
        'entrega',
    ];

    protected $casts = [
        'fecha' => 'date',
        'sistemas' => 'array',
    ];
}
