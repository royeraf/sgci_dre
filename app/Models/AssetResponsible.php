<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetResponsible extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'nombre_original',
        'area_id',
        'oficina_id',
        'contrato_id',
        'employee_id',
        'activo',
    ];
    
    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $appends = [
        'nombre_completo',
    ];

    // ===== RELACIONES CON RRHH =====
    
    /**
     * Dirección del responsable
     */
    public function direction(): BelongsTo
    {
        return $this->belongsTo(HrDirection::class, 'area_id');
    }
    
    /**
     * Oficina del responsable
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(HrOffice::class, 'oficina_id');
    }
    
    /**
     * Tipo de contrato
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(HRContractType::class, 'contrato_id');
    }
    
    /**
     * Empleado vinculado (si existe en el sistema)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    
    // ===== RELACIONES CON PATRIMONIO =====
    
    /**
     * Movimientos donde este responsable está asignado
     */
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class, 'responsable_id');
    }
    
    // ===== ACCESSORS =====
    
    /**
     * Nombre completo del responsable
     * Prioriza el nombre del empleado vinculado, sino usa nombre_original
     */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->employee) {
            return $this->employee->full_name;
        }
        
        return $this->nombre_original ?? 'Sin nombre';
    }
    
    // ===== SCOPES =====
    
    /**
     * Scope para responsables activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope con todas las relaciones
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['direction', 'office', 'contractType', 'employee.person']);
    }
}
