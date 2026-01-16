<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class License extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'dni',
        'tipo_licencia',
        'motivo',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'estado',
        'observaciones',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación con el empleado
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
