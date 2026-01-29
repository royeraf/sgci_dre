<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitReason extends Model
{
    use HasUuids;

    protected $table = 'visit_reasons';

    protected $fillable = [
        'nombre',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the visits for the visit reason.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(ExternalVisit::class, 'visit_reason_id');
    }

    /**
     * Scope a query to only include active reasons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
