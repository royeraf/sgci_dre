<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaDetalle extends Model
{
    use HasUuids;

    protected $table = 'planilla_detalles';

    protected $fillable = [
        'periodo_id',
        'employee_id',
        'remuneracion_base',
        'total_ingresos',
        'total_descuentos',
        'total_aportaciones',
        'neto_pagar',
    ];

    protected $casts = [
        'remuneracion_base' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total_aportaciones' => 'decimal:2',
        'neto_pagar' => 'decimal:2',
    ];

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PlanillaPeriodo::class, 'periodo_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlanillaDetalleItem::class, 'detalle_id')->orderBy('orden');
    }
}
