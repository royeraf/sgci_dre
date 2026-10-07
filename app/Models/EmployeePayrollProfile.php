<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollProfile extends Model
{
    use HasUuids;

    protected $table = 'employee_payroll_profiles';

    protected $fillable = [
        'employee_id',
        'regimen_pensionario_id',
        'cuspp',
        'tipo_comision',
        'banco_id',
        'cuenta_ahorro',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function regimenPensionario(): BelongsTo
    {
        return $this->belongsTo(PlanillaRegimenPensionario::class, 'regimen_pensionario_id');
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(PlanillaBanco::class, 'banco_id');
    }
}
