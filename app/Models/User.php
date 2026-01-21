<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'person_id',
        'username',
        'name',
        'email',
        'password',
        'apellidos',
        'rol_id',
        'is_active',
        'ultimo_acceso',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ultimo_acceso' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the custom role for this user.
     */
    public function customRole()
    {
        return $this->belongsTo(CustomRole::class, 'rol_id', 'rol_id');
    }

    /**
     * Get the occurrences created by this user (as vigilante).
     */
    public function occurrences()
    {
        return $this->hasMany(Occurrence::class, 'vigilante_id');
    }

    /**
     * Get the entry/exits registered by this user.
     */
    public function entryExitsRegistered()
    {
        return $this->hasMany(EntryExit::class, 'registrado_por');
    }

    /**
     * Get the audit logs for this user.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the full name with title.
     */
    public function getFullNameAttribute(): string
    {
        // Si tiene persona asociada, usar datos de person
        if ($this->person) {
            return $this->person->nombres . ' ' . $this->person->apellidos;
        }
        // Fallback a campos directos
        return $this->name . ($this->apellidos ? ' ' . $this->apellidos : '');
    }

    /**
     * Relación con Person (datos personales normalizados)
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Acceso al DNI a través de person
     */
    public function getDniAttribute(): ?string
    {
        return $this->person?->dni;
    }

    /**
     * Acceso al teléfono a través de person
     */
    public function getTelefonoAttribute(): ?string
    {
        return $this->person?->telefono;
    }

    /**
     * Check if user has permission for a module action.
     */
    public function hasModulePermission(string $module, string $action): bool
    {
        if (!$this->customRole) {
            return false;
        }

        $permisos = $this->customRole->permisos_json;
        
        if (!isset($permisos[$module])) {
            return false;
        }

        return in_array($action, $permisos[$module]);
    }
}
