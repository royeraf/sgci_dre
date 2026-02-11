<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalVisit extends Model
{
    use HasFactory;

    protected $table = 'external_visits';

    protected $fillable = [
        'person_id',
        'direction_id',
        'office_id',
        'visit_reason_id',
        'hora_ingreso',
        'hora_salida',
        'motivo',
        'a_quien_visita',
        'fecha',
        'registrado_por',
        'observacion_salida',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_ingreso' => 'datetime:H:i',
        'hora_salida' => 'datetime:H:i',
    ];

    /**
     * Atributos que se agregan a la serialización del modelo (JSON)
     */
    protected $appends = [
        'dni',
        'nombres',
        'direction_nombre',
        'office_nombre',
        'motivo_nombre',
    ];

    /**
     * Relación con la persona (visitante)
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Relación con la dirección que visita
     */
    public function direction(): BelongsTo
    {
        return $this->belongsTo(HrDirection::class, 'direction_id');
    }

    /**
     * Relación con la oficina que visita
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(HrOffice::class, 'office_id');
    }

    /**
     * Relación con el empleado visitado
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relación con el motivo de visita
     */
    public function reason(): BelongsTo
    {
        return $this->belongsTo(VisitReason::class, 'visit_reason_id');
    }

    /**
     * Relación con el usuario que registró la visita
     */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
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
     * Acceso al nombre completo a través de person
     */
    public function getNombresAttribute(): ?string
    {
        return $this->person ? $this->person->nombres . ' ' . $this->person->apellidos : null;
    }

    /**
     * Acceso al nombre de la dirección
     */
    public function getDirectionNombreAttribute(): ?string
    {
        return $this->direction?->nombre;
    }

    /**
     * Acceso al nombre de la oficina
     */
    public function getOfficeNombreAttribute(): ?string
    {
        return $this->office?->nombre;
    }

    /**
     * Acceso al nombre del motivo
     */
    public function getMotivoNombreAttribute(): ?string
    {
        return $this->reason?->nombre;
    }

    /**
     * Nombre completo del visitante
     */
    public function getNombreCompletoAttribute(): ?string
    {
        return $this->person?->nombre_completo;
    }

    // ===== SCOPES =====

    /**
     * Scope para visitas de hoy
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', today());
    }

    /**
     * Scope para visitas pendientes (sin hora de salida)
     */
    public function scopePendientes($query)
    {
        return $query->whereNull('hora_salida');
    }

    /**
     * Scope con todas las relaciones
     */
    public function scopeWithAllRelations($query)
    {
        return $query->with(['person', 'direction', 'office', 'registrador']);
    }
}
