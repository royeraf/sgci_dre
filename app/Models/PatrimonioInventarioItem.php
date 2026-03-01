<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrimonioInventarioItem extends Model
{
    protected $table = 'patrimonio_inventario_items';

    protected $fillable = [
        'inventario_id',
        'asset_id',
        'estado_id',
        'oficina_id',
        'responsable_anterior_id',
        'responsable_id',
        'observaciones',
        'inventariador_id',
        'fecha_verificacion',
    ];

    protected $casts = [
        'fecha_verificacion' => 'date',
    ];

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(PatrimonioInventario::class, 'inventario_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(AssetState::class, 'estado_id');
    }

    public function oficina(): BelongsTo
    {
        return $this->belongsTo(HrOffice::class, 'oficina_id');
    }

    public function responsableAnterior(): BelongsTo
    {
        return $this->belongsTo(AssetResponsible::class, 'responsable_anterior_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(AssetResponsible::class, 'responsable_id');
    }

    public function inventariador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inventariador_id');
    }
}
