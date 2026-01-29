<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetResponsible extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'nombre_original',
        'nombre_normalizado',
        'area',
        'tipo_contrato',
        'activo',
        'employee_id'
    ];
    
    protected $casts = [
        'activo' => 'boolean',
    ];
}
