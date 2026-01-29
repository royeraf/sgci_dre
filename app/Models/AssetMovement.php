<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetMovement extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'asset_id',
        'tipo',
        'fecha',
        'ubicacion_actual',
        'responsable_nombre',
        'responsable_id',
        'modalidad_responsable',
        'inventariador_id',
        'documento_id',
        'observaciones',
        'estado'
    ];
    
    protected $casts = [
        'fecha' => 'date',
    ];
    
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
    
    public function responsible()
    {
        return $this->belongsTo(AssetResponsible::class, 'responsable_id');
    }
    
    public function inventariador()
    {
        return $this->belongsTo(User::class, 'inventariador_id');
    }
}
