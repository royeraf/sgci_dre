<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gratificacion extends Model
{
    use HasUuids;

    protected $table = 'gratificaciones';

    protected $fillable = [
        'employee_id',
        'anio',
        'periodo',
        'remuneracion_corte',
        'porcentaje_aplicado',
        'meses_completos',
        'dias',
        'base_semestral',
        'monto_proporcional',
        'monto_minimo',
        'monto_final',
        'aporte_essalud',
        'registrado_por',
    ];

    protected $casts = [
        'anio' => 'integer',
        'remuneracion_corte' => 'decimal:2',
        'porcentaje_aplicado' => 'decimal:4',
        'meses_completos' => 'integer',
        'dias' => 'integer',
        'base_semestral' => 'decimal:2',
        'monto_proporcional' => 'decimal:2',
        'monto_minimo' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'aporte_essalud' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
