<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anotación libre de un empleado, persistente entre planillas.
 */
class EmployeeNote extends Model
{
    use HasUuids;

    protected $table = 'employee_notes';

    protected $fillable = [
        'employee_id',
        'texto',
        'registrado_por',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
