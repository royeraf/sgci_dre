<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMovement extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'asset_id',
        'tipo',
        'fecha',
        'responsable_id',
        'estado_id',
        'oficina_id',
        'area_id',
        'inventariador_id',
        'observaciones',
    ];
    
    protected $casts = [
        'fecha' => 'date',
    ];

    // ===== RELACIONES =====
    
    /**
     * Activo al que pertenece el movimiento
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
    
    /**
     * Responsable asignado en este movimiento
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(AssetResponsible::class, 'responsable_id');
    }
    
    /**
     * Estado del bien en este movimiento
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(AssetState::class, 'estado_id');
    }
    
    /**
     * Oficina/ubicación en este movimiento
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(HrOffice::class, 'oficina_id');
    }

    /**
     * Área/dirección en este movimiento
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(HRArea::class, 'area_id');
    }
    
    /**
     * Usuario que registró el movimiento
     */
    public function inventariador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inventariador_id');
    }

    // ===== SCOPES =====
    
    /**
     * Scope para cargar relaciones comunes
     */
    public function scopeWithRelations($query)
    {
        return $query->with([
            'responsible.employee.person',
            'state',
            'office.area',
            'inventariador',
        ]);
    }
}
