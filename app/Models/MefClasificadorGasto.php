<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MefClasificadorGasto extends Model
{
    use HasUuids;

    protected $table = 'mef_clasificador_gasto';

    protected $fillable = [
        'anio',
        'codigo',
        'codigo_alias',
        'codigo_padre',
        'nivel',
        'descripcion',
        'es_terminal',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'fuente',
        'version_catalogo',
    ];

    protected $casts = [
        'anio' => 'integer',
        'nivel' => 'integer',
        'es_terminal' => 'boolean',
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function reglas(): HasMany
    {
        return $this->hasMany(MefReglaClasificacionGasto::class, 'clasificador_id');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(MefClasificadorGastoHistorial::class, 'clasificador_id');
    }

    public function scopeDelAnio($query, int $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
