<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaPeriodo extends Model
{
    use HasUuids;

    public const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Setiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    protected $table = 'planilla_periodos';

    protected $fillable = [
        'anio',
        'mes',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'total_empleados',
        'total_neto',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'total_empleados' => 'integer',
        'total_neto' => 'decimal:2',
    ];

    protected $appends = ['nombre_periodo', 'editable'];

    public function detalles(): HasMany
    {
        return $this->hasMany(PlanillaDetalle::class, 'periodo_id');
    }

    public function getNombrePeriodoAttribute(): string
    {
        $mes = self::MESES[$this->mes] ?? $this->mes;

        return "{$mes} {$this->anio}";
    }

    /**
     * Solo se puede (re)generar en BORRADOR o CALCULADA.
     */
    public function getEditableAttribute(): bool
    {
        return in_array($this->estado, ['BORRADOR', 'CALCULADA'], true);
    }
}
