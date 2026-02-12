<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'direction_id',
        'position_id',
        'office_id',
        'contract_type_id',
        'fecha_ingreso',
        'estado',
        'observaciones',
        'licencias_totales',
        'licencias_usadas',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
    ];

    /**
     * Atributos que se agregan a la serialización del modelo (JSON)
     */
    protected $appends = [
        'dni',
        'nombres',
        'apellidos',
        'telefono',
        'correo',
        'cargo',
        'direction_nombre',
        'tipo_contrato', // Virtual attribute
        'full_name',
    ];

    /**
     * Relación con la persona base
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Relación con la dirección
     */
    public function direction(): BelongsTo
    {
        return $this->belongsTo(HrDirection::class, 'direction_id');
    }

    /**
     * Relación con el cargo/posición
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(HRPosition::class, 'position_id');
    }

    /**
     * Relación con la oficina
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(HrOffice::class, 'office_id');
    }

    /**
     * Relación con el tipo de contrato
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(HRContractType::class, 'contract_type_id');
    }

    /**
     * Relación con vacaciones
     */
    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class, 'empleado_id');
    }

    /**
     * Relación con licencias
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'employee_id');
    }

    /**
     * Relación con entradas/salidas (papeletas)
     */
    public function entryExits(): HasMany
    {
        return $this->hasMany(EntryExit::class, 'employee_id');
    }

    // ===== ACCESSORS para compatibilidad =====

    /**
     * Acceso al DNI a través de person
     */
    public function getDniAttribute(): ?string
    {
        return $this->person?->dni;
    }

    /**
     * Acceso a nombres a través de person
     */
    public function getNombresAttribute(): ?string
    {
        return $this->person?->nombres;
    }

    /**
     * Acceso a apellidos a través de person
     */
    public function getApellidosAttribute(): ?string
    {
        return $this->person?->apellidos;
    }

    /**
     * Acceso al teléfono a través de person
     */
    public function getTelefonoAttribute(): ?string
    {
        return $this->person?->telefono;
    }

    /**
     * Acceso al email a través de person
     */
    public function getCorreoAttribute(): ?string
    {
        return $this->person?->email;
    }

    /**
     * Acceso al cargo a través de position
     */
    public function getCargoAttribute(): ?string
    {
        return $this->position?->nombre;
    }

    /**
     * Acceso a la dirección (nombre) a través de direction
     */
    public function getDirectionNombreAttribute(): ?string
    {
        return $this->direction?->nombre;
    }

    /**
     * Acceso al tipo de contrato (nombre) a través de contractType
     */
    public function getTipoContratoAttribute(): ?string
    {
        return $this->contractType?->nombre;
    }

    /**
     * Nombre completo del empleado
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    /**
     * Nombre completo formato: Apellidos, Nombres
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellidos}, {$this->nombres}";
    }

    // ===== SCOPES =====

    /**
     * Scope para empleados activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'ACTIVO');
    }

    /**
     * Scope para incluir datos de persona
     */
    public function scopeWithPerson($query)
    {
        return $query->with('person');
    }

    /**
     * Scope para incluir todas las relaciones comunes
     */
    public function scopeWithAllRelations($query)
    {
        return $query->with(['person', 'direction', 'position', 'office', 'contractType']);
    }
}
