<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MefReglaClasificacionGasto extends Model
{
    use HasUuids;

    protected $table = 'mef_reglas_clasificacion_gasto';

    protected $fillable = [
        'anio',
        'regimen',
        'modalidad',
        'concepto_codigo',
        'clasificador_id',
        'prioridad',
        'activo',
        'fuente',
    ];

    protected $casts = [
        'anio' => 'integer',
        'prioridad' => 'integer',
        'activo' => 'boolean',
    ];

    public function clasificador(): BelongsTo
    {
        return $this->belongsTo(MefClasificadorGasto::class, 'clasificador_id');
    }
}
