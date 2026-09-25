<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PlanillaParametroAfp extends Model
{
    protected $table = 'planilla_parametros_afp';

    protected $fillable = [
        'mes',
        'aporte_obligatorio',
        'prima_seguro',
        'remuneracion_maxima_asegurable',
    ];

    protected $casts = [
        'mes' => 'date',
        'aporte_obligatorio' => 'decimal:4',
        'prima_seguro' => 'decimal:4',
        'remuneracion_maxima_asegurable' => 'decimal:2',
    ];

    public static function vigente(Carbon $mes): ?self
    {
        $clave = $mes->copy()->startOfMonth();

        return static::where('mes', '<=', $clave)
            ->orderByDesc('mes')
            ->first()
            ?? static::orderBy('mes')->first();
    }
}
