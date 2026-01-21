<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'people';

    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'genero',
        'direccion',
        'telefono',
        'email',
        'tipo',
        'is_active',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener el nombre completo de la persona
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellidos}, {$this->nombres}";
    }

    /**
     * Obtener el nombre completo en formato normal
     */
    public function getNombreFullAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    /**
     * Relación con empleado (si es persona interna)
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'person_id');
    }

    /**
     * Relación con usuario del sistema
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'person_id');
    }

    /**
     * Relación con citas
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'person_id');
    }

    /**
     * Relación con visitas externas
     */
    public function externalVisits(): HasMany
    {
        return $this->hasMany(ExternalVisit::class, 'person_id');
    }

    /**
     * Scope para personas internas
     */
    public function scopeInternas($query)
    {
        return $query->where('tipo', 'INTERNO');
    }

    /**
     * Scope para personas externas
     */
    public function scopeExternas($query)
    {
        return $query->where('tipo', 'EXTERNO');
    }

    /**
     * Scope para personas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Buscar por DNI
     */
    public static function findByDni(string $dni): ?self
    {
        return static::where('dni', $dni)->first();
    }
}
