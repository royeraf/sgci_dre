<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Suplente autorizado a aprobar papeletas en nombre del jefe inmediato
 * titular de una oficina o dirección (encargatura por vacaciones, licencia,
 * comisión, etc.). El titular sigue viviendo en jefe_inmediato_id de
 * HrOffice/HrDirection; esta tabla solo agrega aprobadores adicionales.
 */
class JefeSuplente extends Model
{
    use HasUuids;

    protected $table = 'hr_jefe_suplentes';

    protected $fillable = [
        'suplentable_type',
        'suplentable_id',
        'employee_id',
        'vigente_desde',
        'vigente_hasta',
        'activo',
        'observacion',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'activo' => 'boolean',
    ];

    protected $appends = ['es_vigente'];

    public function suplentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Activo y, si tiene fechas, dentro del rango de vigencia (encargatura).
     * Sin fechas se considera suplente permanente.
     */
    public function scopeVigente($query)
    {
        $hoy = now()->toDateString();

        return $query->where('activo', true)
            ->where(fn ($q) => $q->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $hoy))
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $hoy));
    }

    public function getEsVigenteAttribute(): bool
    {
        if (!$this->activo) {
            return false;
        }

        $hoy = now()->toDateString();

        if ($this->vigente_desde && $this->vigente_desde->toDateString() > $hoy) {
            return false;
        }

        if ($this->vigente_hasta && $this->vigente_hasta->toDateString() < $hoy) {
            return false;
        }

        return true;
    }
}
