<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrimonioAsset extends Model
{
    protected $table = 'patrimonio_assets';

    protected $fillable = [
        'codigo_patrimonial', 'codigo_interno', 'codigo_activo',
        'denominacion', 'descripcion', 'caracteristicas', 'observaciones',
        'marca', 'modelo', 'nro_serie', 'medidas',
        'estado_conserv', 'estado_desc',
        'responsable_final', 'empleado_final', 'oficina',
        'fecha_compra', 'fecha_alta',
        'valor_compra', 'valor_inicial', 'valor_deprec',
        'codigo_barra',
        'archivo_origen', 'fecha_importacion', 'importado_por', 'lote_importacion',
        'asset_id', 'sincronizado', 'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_alta' => 'date',
        'fecha_importacion' => 'datetime',
        'fecha_sincronizacion' => 'datetime',
        'sincronizado' => 'boolean',
        'valor_compra' => 'decimal:6',
        'valor_inicial' => 'decimal:2',
        'valor_deprec' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'importado_por');
    }
}
