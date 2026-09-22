<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaDetalleItem extends Model
{
    use HasUuids;

    protected $table = 'planilla_detalle_items';

    protected $fillable = [
        'detalle_id',
        'concepto_id',
        'tipo',
        'descripcion',
        'base_calculo',
        'porcentaje',
        'monto',
        'orden',
    ];

    protected $casts = [
        'base_calculo' => 'decimal:2',
        'porcentaje' => 'decimal:5',
        'monto' => 'decimal:2',
        'orden' => 'integer',
    ];

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(PlanillaDetalle::class, 'detalle_id');
    }

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(PlanillaConcepto::class, 'concepto_id');
    }
}
