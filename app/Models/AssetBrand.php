<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Bienes de esta marca
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'marca_id');
    }

    /**
     * Scope para marcas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
