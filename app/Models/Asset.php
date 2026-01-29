<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'codigo_patrimonio',
        'codigo_interno',
        'codigo_completo',
        'detalle_bien',
        'descripcion',
        'categoria_id',
        'marca',
        'modelo',
        'numero_serie',
        'dimension',
        'color',
        'fecha_adquisicion',
        'fecha_asignacion',
        'fecha_retiro',
        'tipo_origen',
        'estado',
        'responsable_id',
        'tipo_modalidad',
        'inventariador_id',
        'observacion',
        'codigo_barras',
        'codigo_patrimonial',
        'oficina',
        'fuente',
        'tipo_registro',
    ];
    
    protected $casts = [
        'fecha_adquisicion' => 'date',
        'fecha_asignacion' => 'date',
        'fecha_retiro' => 'date',
    ];
    
    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'categoria_id');
    }
    
    public function responsible()
    {
        return $this->belongsTo(AssetResponsible::class, 'responsable_id');
    }
    
    public function inventariador()
    {
        return $this->belongsTo(User::class, 'inventariador_id');
    }
    
    public function movements()
    {
        return $this->hasMany(AssetMovement::class);
    }
    
    // Helper to get current location/responsible from latest movement if needed, 
    // although we store current responsible_id in assets table too.
    public function getLocationAttribute()
    {
        // Logic to get location from movements or internal field
        // For now, let's use the one from relation or movement
        return $this->movements()->latest('fecha')->first()?->ubicacion_actual;
    }
}
