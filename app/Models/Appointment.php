<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni',
        'oficina',
        'apellidos',
        'nombres',
        'fecha',
        'hora',
        'celular',
        'correo',
        'asunto',
        'estado',
        'historial',
    ];

    protected $casts = [
        'historial' => 'array',
        'fecha' => 'date',
    ];
}
