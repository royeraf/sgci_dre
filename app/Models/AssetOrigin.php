<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetOrigin extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Bienes de este origen
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'origen_id');
    }

    /**
     * Scope para orígenes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
