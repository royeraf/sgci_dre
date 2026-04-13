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
        'dni',
        'name',
        'email',
        'password',
        'titulo',
        'apellidos',
        'cargo',
        'area',
        'telefono',
        'rol_id',
        'modulos_json',
        'tabs_json',
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
        'modulos_json' => 'array',
        'tabs_json'    => 'array',
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
     * @var array<int, string>
     */
    protected $appends = [
        'full_name',
        'dni',
        'telefono',
    ];

    /**
     * Nombre del usuario: de people si está vinculado, si no del campo directo.
     */
    public function getNameAttribute(): ?string
    {
        if ($this->person_id) {
            $person = $this->relationLoaded('person') ? $this->person : $this->person()->first();
            return $person?->nombres ?? $this->attributes['name'] ?? null;
        }
        return $this->attributes['name'] ?? null;
    }

    /**
     * Apellidos del usuario: de people si está vinculado, si no del campo directo.
     */
    public function getApellidosAttribute(): ?string
    {
        if ($this->person_id) {
            $person = $this->relationLoaded('person') ? $this->person : $this->person()->first();
            return $person?->apellidos ?? $this->attributes['apellidos'] ?? null;
        }
        return $this->attributes['apellidos'] ?? null;
    }

    /**
     * Email del usuario: de people si está vinculado, si no del campo directo.
     */
    public function getEmailAttribute(): ?string
    {
        if ($this->person_id) {
            $person = $this->relationLoaded('person') ? $this->person : $this->person()->first();
            return $person?->email ?? $this->attributes['email'] ?? null;
        }
        return $this->attributes['email'] ?? null;
    }

    /**
     * Get the full name with title.
     */
    public function getFullNameAttribute(): string
    {
        $nombre    = $this->getNameAttribute() ?? '';
        $apellidos = $this->getApellidosAttribute() ?? '';
        $titulo    = $this->attributes['titulo'] ?? '';
        $titulo    = $titulo ? $titulo . ' ' : '';

        return trim($titulo . $nombre . ($apellidos ? ' ' . $apellidos : ''));
    }

    /**
     * Relación con Person (datos personales normalizados)
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Obtener el Employee asociado al usuario (a través de person)
     */
    public function getEmployeeAttribute(): ?Employee
    {
        if (!$this->person_id) return null;
        return Employee::where('person_id', $this->person_id)->first();
    }

    /**
     * Acceso al DNI a través de person con fallback
     */
    public function getDniAttribute(): ?string
    {
        return $this->person?->dni ?? $this->attributes['dni'] ?? null;
    }

    /**
     * Acceso al teléfono a través de person con fallback
     */
    public function getTelefonoAttribute(): ?string
    {
        return $this->person?->telefono ?? $this->attributes['telefono'] ?? null;
    }

    /**
     * Check if user has permission for a module action.
     * If the user has explicit modulos_json set, those override role-based permissions.
     */
    public function hasModulePermission(string $module, string $action): bool
    {
        // Per-user module overrides: if set, only these modules are allowed
        if (!empty($this->modulos_json)) {
            return in_array($module, $this->modulos_json);
        }

        // Fall back to role-based permissions
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
