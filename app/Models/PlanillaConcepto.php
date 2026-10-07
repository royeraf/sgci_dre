<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaConcepto extends Model
{
    use HasUuids;

    protected $table = 'planilla_conceptos';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'categoria',
        'afecto_renta5',
        'afecto_essalud',
        'afecto_onp',
        'afecto_afp',
        'es_porcentaje',
        'valor',
        'orden',
        'activo',
    ];

    protected $casts = [
        'afecto_renta5' => 'boolean',
        'afecto_essalud' => 'boolean',
        'afecto_onp' => 'boolean',
        'afecto_afp' => 'boolean',
        'es_porcentaje' => 'boolean',
        'valor' => 'decimal:5',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(PlanillaConceptoAsignacion::class, 'concepto_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}
