<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MefClasificadorGastoHistorial extends Model
{
    use HasUuids;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'mef_clasificador_gasto_historial';

    protected $fillable = [
        'clasificador_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'fecha_cambio',
        'fuente',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    public function clasificador(): BelongsTo
    {
        return $this->belongsTo(MefClasificadorGasto::class, 'clasificador_id');
    }
}
