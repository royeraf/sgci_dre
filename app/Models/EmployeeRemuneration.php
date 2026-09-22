<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRemuneration extends Model
{
    use HasUuids;

    protected $fillable = [
        'employee_id',
        'monto',
        'tipo',
        'desde',
        'hasta',
        'motivo',
        'created_by',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'desde' => 'date',
        'hasta' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Remuneraciones vigentes en una fecha dada.
     */
    public function scopeVigenteEn($query, $fecha)
    {
        return $query->where('desde', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('hasta')->orWhere('hasta', '>=', $fecha);
            });
    }
}
