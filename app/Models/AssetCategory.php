<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['nombre'];

    /**
     * Bienes de esta categoría
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'categoria_id');
    }
}
