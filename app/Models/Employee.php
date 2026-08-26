<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'direction_id',
        'position_id',
        'encargatura_id',
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
        'encargatura',
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
     * Relación con el cargo de encargatura
     */
    public function encargaturaPosition(): BelongsTo
    {
        return $this->belongsTo(HRPosition::class, 'encargatura_id');
    }

    /**
     * Relación con la licencia de conducir (si el empleado es conductor)
     */
    public function driverLicense(): HasOne
    {
        return $this->hasOne(DriverLicense::class, 'employee_id');
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

    /**
     * Relación con solicitudes de papeleta
     */
    public function papeletaRequests(): HasMany
    {
        return $this->hasMany(PapeletaRequest::class, 'employee_id');
    }

    /**
     * Obtener el jefe inmediato: primero busca en office, luego fallback a direction
     */
    public function getJefeInmediatoAttribute(): ?Employee
    {
        if ($this->office && $this->office->jefe_inmediato_id) {
            return $this->office->jefeInmediato;
        }
        if ($this->direction && $this->direction->jefe_inmediato_id) {
            return $this->direction->jefeInmediato;
        }
        return null;
    }

    /**
     * Relación inversa: oficinas donde este empleado es el jefe titular.
     */
    public function officesLideradas(): HasMany
    {
        return $this->hasMany(HrOffice::class, 'jefe_inmediato_id');
    }

    /**
     * Relación inversa: direcciones donde este empleado es el jefe titular.
     */
    public function directionsLideradas(): HasMany
    {
        return $this->hasMany(HrDirection::class, 'jefe_inmediato_id');
    }

    /**
     * Designaciones de suplencia de este empleado (como suplente de otros).
     */
    public function suplencias(): HasMany
    {
        return $this->hasMany(JefeSuplente::class, 'employee_id');
    }

    /**
     * La unidad (oficina u oficina de respaldo dirección) que resuelve quién
     * puede aprobar las papeletas de este empleado: oficina primero, y solo
     * si esta no tiene ningún aprobador (ni titular ni suplente vigente) cae
     * a la dirección.
     */
    public function unidadAprobadora(): HrOffice|HrDirection|null
    {
        if ($this->office && $this->office->tieneAprobadores()) {
            return $this->office;
        }
        if ($this->direction && $this->direction->tieneAprobadores()) {
            return $this->direction;
        }
        return null;
    }

    /**
     * Todos los que pueden aprobar las papeletas de este empleado: el jefe
     * titular de su unidad más los suplentes vigentes.
     */
    public function getAprobadoresPapeletaAttribute(): Collection
    {
        return $this->unidadAprobadora()?->aprobadores() ?? collect();
    }

    /**
     * ¿Este empleado ($this) puede aprobar las papeletas de $solicitante?
     * Mecanismo LEGADO: solo se consulta para papeletas sin un
     * jefe_asignado_id explícito (ver PapeletaRequest::aprobadoresEsperados()).
     */
    public function puedeAprobarPapeletasDe(Employee $solicitante): bool
    {
        return $solicitante->aprobadores_papeleta->contains('id', $this->id);
    }

    /**
     * ¿Este empleado es aprobador de papeletas de alguien? (titular de
     * alguna oficina/dirección, o suplente vigente de alguna). Es un hecho
     * de la designación de RR.HH., independiente de si además fue elegido a
     * mano en alguna papeleta puntual (ver participaEnEtapaJefe()).
     */
    public function esAprobadorDeAlguien(): bool
    {
        return $this->officesLideradas()->exists()
            || $this->directionsLideradas()->exists()
            || $this->suplencias()->vigente()->exists();
    }

    /**
     * Ids de oficinas donde este empleado puede firmar la etapa "jefe":
     * las que lidera como titular, más las que suple vigentemente.
     */
    public function officeIdsLideradas(): Collection
    {
        return $this->officesLideradas()->pluck('id')
            ->merge($this->suplencias()->vigente()->where('suplentable_type', HrOffice::class)->pluck('suplentable_id'))
            ->unique();
    }

    /**
     * Ids de direcciones donde este empleado puede firmar la etapa "jefe":
     * las que lidera como titular, más las que suple vigentemente.
     */
    public function directionIdsLideradas(): Collection
    {
        return $this->directionsLideradas()->pluck('id')
            ->merge($this->suplencias()->vigente()->where('suplentable_type', HrDirection::class)->pluck('suplentable_id'))
            ->unique();
    }

    /**
     * ¿Alguna papeleta fue dirigida explícitamente a este empleado como
     * jefe inmediato elegido por el solicitante?
     */
    public function tienePapeletasAsignadas(): bool
    {
        return PapeletaRequest::where('jefe_asignado_id', $this->id)->exists();
    }

    /**
     * Gate real de acceso a la bandeja/menú de la etapa "jefe": está
     * designado en Direcciones/Oficinas, O fue elegido a mano en alguna
     * papeleta puntual (cualquier empleado activo puede serlo, sin ser
     * titular/suplente de nada).
     */
    public function participaEnEtapaJefe(): bool
    {
        return $this->esAprobadorDeAlguien() || $this->tienePapeletasAsignadas();
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
     * Acceso a la encargatura (nombre) a través de encargaturaPosition
     */
    public function getEncargaturaAttribute(): ?string
    {
        return $this->encargaturaPosition?->nombre;
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
