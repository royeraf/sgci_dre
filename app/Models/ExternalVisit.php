<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni',
        'nombres',
        'hora_ingreso',
        'hora_salida',
        'motivo',
        'area',
        'a_quien_visita',
        'fecha',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_ingreso' => 'datetime:H:i',
        'hora_salida' => 'datetime:H:i',
    ];

    public function registrarUser()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
