<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaConceptoAsignacion extends Model
{
    use HasUuids;

    protected $table = 'planilla_concepto_asignaciones';

    protected $fillable = [
        'concepto_id',
        'employee_id',
        'contract_type_id',
        'monto',
        'porcentaje',
        'desde',
        'hasta',
        'activo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'porcentaje' => 'decimal:5',
        'desde' => 'date',
        'hasta' => 'date',
        'activo' => 'boolean',
    ];

    public function concepto(): BelongsTo
    {
        return $this->belongsTo(PlanillaConcepto::class, 'concepto_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(HRContractType::class, 'contract_type_id');
    }
}
