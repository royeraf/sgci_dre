<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo_hex',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Bienes de este color
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'color_id');
    }

    /**
     * Scope para colores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
