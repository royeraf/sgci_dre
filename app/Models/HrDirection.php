<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class HrDirection extends Model
{
    use HasUuids;

    protected $table = 'hr_directions';

    protected $fillable = [
        'nombre',
        'abreviacion',
        'codigo',
        'descripcion',
        'telefono_interno',
        'ubicacion',
        'activo',
        'jefe_inmediato_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con oficinas de la dirección
     */
    public function offices(): HasMany
    {
        return $this->hasMany(HrOffice::class, 'direction_id');
    }

    /**
     * Relación con empleados de la dirección
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'direction_id');
    }

    /**
     * Relacion con el jefe inmediato de la direccion
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
     * Hay alguien que pueda aprobar papeletas de esta dirección: el titular
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
     * Scope para direcciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
