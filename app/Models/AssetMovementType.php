<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetMovementType extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Movimientos con este tipo
     */
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class, 'tipo_movimiento_id');
    }

    /**
     * Scope para tipos activos ordenados
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('nombre');
    }
}
