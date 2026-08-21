<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class HrOffice extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_offices';

    protected $fillable = [
        'direction_id',
        'codigo',
        'nombre',
        'ubicacion',
        'piso',
        'telefono_interno',
        'activo',
        'jefe_inmediato_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con la dirección
     */
    public function direction(): BelongsTo
    {
        return $this->belongsTo(HrDirection::class, 'direction_id');
    }

    /**
     * Relación con empleados asignados a esta oficina
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'office_id');
    }

    /**
     * Relacion con el jefe inmediato de la oficina
     */
    public function jefeInmediato(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'jefe_inmediato_id');
    }

    /**
     * Suplentes autorizados a aprobar papeletas en nombre del jefe inmediato
     * (encargaturas por vacaciones, licencia, comisión, etc.)
     */
    public function suplentes(): MorphMany
    {
        return $this->morphMany(JefeSuplente::class, 'suplentable');
    }

    /**
     * Hay alguien que pueda aprobar papeletas de esta oficina: el titular
     * o al menos un suplente vigente.
     */
    public function tieneAprobadores(): bool
    {
        return $this->jefe_inmediato_id !== null
            || $this->suplentes()->vigente()->exists();
    }

    /**
     * Titular (si existe) + suplentes vigentes, deduplicados por id.
     *
     * @return Collection<int, Employee>
     */
    public function aprobadores(): Collection
    {
        $aprobadores = collect();

        if ($this->jefeInmediato) {
            $aprobadores->push($this->jefeInmediato);
        }

        $this->suplentes()->vigente()->with('employee.person')->get()
            ->each(function (JefeSuplente $suplente) use ($aprobadores) {
                if ($suplente->employee) {
                    $aprobadores->push($suplente->employee);
                }
            });

        return $aprobadores->unique('id')->values();
    }

    /**
     * Scope para oficinas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener nombre completo con dirección
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->direction?->nombre} - {$this->nombre}";
    }
}
