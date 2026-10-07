<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GratificacionCasParametro extends Model
{
    protected $table = 'gratificacion_cas_parametros';

    protected $fillable = [
        'anio_fiscal',
        'porcentaje',
        'monto_minimo',
        'fecha_vigencia_norma',
        'activo',
    ];

    protected $casts = [
        'anio_fiscal' => 'integer',
        'porcentaje' => 'decimal:4',
        'monto_minimo' => 'decimal:2',
        'fecha_vigencia_norma' => 'date',
        'activo' => 'boolean',
    ];

    public function scopeVigente($query)
    {
        return $query->where('activo', true);
    }
}
