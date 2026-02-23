<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\PatrimonioAsset;

class Asset extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'codigo_patrimonio',
        'codigo_interno',
        'codigo_completo',
        'denominacion',
        'descripcion',
        'categoria_id',
        'marca_id',
        'color_id',
        'origen_id',
        'modelo',
        'numero_serie',
        'dimension',
        'fecha_adquisicion',
        'fecha_retiro',
        'observacion',
        'codigo_barras',
    ];
    
    protected $casts = [
        'fecha_adquisicion' => 'date',
        'fecha_retiro' => 'date',
    ];

    protected $appends = [
        'estado_actual',
        'responsable_actual',
        'oficina_actual',
    ];
    
    // ===== RELACIONES CON CATÁLOGOS =====
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'categoria_id');
    }
    
    public function brand(): BelongsTo
    {
        return $this->belongsTo(AssetBrand::class, 'marca_id');
    }
    
    public function color(): BelongsTo
    {
        return $this->belongsTo(AssetColor::class, 'color_id');
    }
    
    public function origin(): BelongsTo
    {
        return $this->belongsTo(AssetOrigin::class, 'origen_id');
    }
    
    // ===== RELACIÓN CON MOVIMIENTOS =====
    
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }

    /**
     * Registro SIGA vinculado (si fue sincronizado desde patrimonio_assets)
     */
    public function patrimonioAsset(): HasOne
    {
        return $this->hasOne(PatrimonioAsset::class);
    }
    
    /**
     * Obtiene el último movimiento del activo
     */
    public function latestMovement(): HasOne
    {
        return $this->hasOne(AssetMovement::class)->latestOfMany('fecha');
    }
    
    // ===== ACCESSORS PARA DATOS DINÁMICOS (del último movimiento) =====
    
    /**
     * Estado actual del bien (del último movimiento)
     */
    public function getEstadoActualAttribute(): ?string
    {
        return $this->latestMovement?->state?->nombre;
    }
    
    /**
     * Responsable actual (del último movimiento)
     */
    public function getResponsableActualAttribute(): ?AssetResponsible
    {
        return $this->latestMovement?->responsible;
    }
    
    /**
     * Oficina/ubicación actual (del último movimiento)
     */
    public function getOficinaActualAttribute(): ?HrOffice
    {
        return $this->latestMovement?->office;
    }
    
    /**
     * Nombre del responsable actual (para mostrar en listas)
     */
    public function getNombreResponsableAttribute(): ?string
    {
        $responsible = $this->responsable_actual;
        if (!$responsible) {
            return null;
        }
        
        // Primero intenta el nombre del empleado vinculado
        if ($responsible->employee) {
            return $responsible->employee->full_name;
        }
        
        // Fallback al nombre original
        return $responsible->nombre_original;
    }
    
    /**
     * Nombre de la oficina actual
     */
    public function getNombreOficinaAttribute(): ?string
    {
        return $this->oficina_actual?->nombre_completo;
    }
}
