<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Vacation extends Model
{
    use HasUuids;

    protected $fillable = [
        'empleado_id',
        'dni',
        'periodo',
        'fecha_inicio',
        'fecha_fin',
        'dias_tomados',
        'dias_pendientes',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación con empleado
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empleado_id');
    }
}
