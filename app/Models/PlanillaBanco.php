<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaBanco extends Model
{
    use HasUuids;

    protected $table = 'planilla_bancos';

    protected $fillable = [
        'nombre',
        'codigo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function payrollProfiles(): HasMany
    {
        return $this->hasMany(EmployeePayrollProfile::class, 'banco_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
