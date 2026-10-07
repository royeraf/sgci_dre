<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanillaParametro extends Model
{
    protected $table = 'planilla_parametros';

    protected $fillable = [
        'anio',
        'uit',
        'pct_tope_essalud',
        'rmv',
        'tasa_essalud',
        'activo',
    ];

    protected $casts = [
        'anio' => 'integer',
        'uit' => 'decimal:2',
        'pct_tope_essalud' => 'decimal:4',
        'rmv' => 'decimal:2',
        'tasa_essalud' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public static function vigente(int $anio): ?self
    {
        return static::where('activo', true)
            ->where('anio', '<=', $anio)
            ->orderByDesc('anio')
            ->first()
            ?? static::where('activo', true)->orderByDesc('anio')->first();
    }

    public function topeEssalud(): float
    {
        return round((float) $this->uit * (float) $this->pct_tope_essalud, 2);
    }
}
