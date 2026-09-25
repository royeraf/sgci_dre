<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaRegimenPensionario extends Model
{
    use HasUuids;

    protected $table = 'planilla_regimenes_pensionarios';

    protected $fillable = [
        'nombre',
        'tipo',
        'aporte_obligatorio',
        'prima_seguro',
        'activo',
    ];

    protected $casts = [
        'aporte_obligatorio' => 'decimal:5',
        'prima_seguro' => 'decimal:5',
        'activo' => 'boolean',
    ];

    public function payrollProfiles(): HasMany
    {
        return $this->hasMany(EmployeePayrollProfile::class, 'regimen_pensionario_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
