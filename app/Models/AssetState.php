<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetState extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Movimientos con este estado
     */
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class, 'estado_id');
    }

    /**
     * Scope para estados activos ordenados
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
