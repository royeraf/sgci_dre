<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro diario de faltas/tardanzas de un empleado en un periodo.
 *
 * Guarda el snapshot de los importes unitarios (valor_dia / valor_minuto)
 * al momento de registrar, igual que la hoja «Dscto. Tard.» del Excel.
 */
class PlanillaTardanza extends Model
{
    use HasUuids;

    public const ORIGEN_MANUAL = 'MANUAL';

    public const ORIGEN_ASISTENCIA = 'ASISTENCIA';

    protected $table = 'planilla_tardanzas';

    protected $fillable = [
        'periodo_id',
        'employee_id',
        'fecha',
        'dias',
        'minutos',
        'valor_dia',
        'valor_minuto',
        'monto_dias',
        'monto_minutos',
        'total',
        'origen',
        'justificado',
        'observacion',
        'registrado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'dias' => 'integer',
        'minutos' => 'integer',
        'valor_dia' => 'decimal:2',
        'valor_minuto' => 'decimal:4',
        'monto_dias' => 'decimal:2',
        'monto_minutos' => 'decimal:2',
        'total' => 'decimal:2',
        'justificado' => 'boolean',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PlanillaPeriodo::class, 'periodo_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /** Solo lo no justificado entra al descuento de la planilla. */
    public function scopeNoJustificadas($query)
    {
        return $query->where('justificado', false);
    }
}
