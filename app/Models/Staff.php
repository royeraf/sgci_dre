<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'dni',
        'nombres',
        'apellidos',
        'cargo',
        'area',
        'regimen',
        'telefono',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the entry/exits for this staff member.
     */
    public function entryExits()
    {
        return $this->hasMany(EntryExit::class);
    }

    /**
     * Scope to get only active staff.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->nombres . ' ' . $this->apellidos;
    }
}
