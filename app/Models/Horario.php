<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'nombre',
        'entrada_manana',
        'salida_manana',
        'entrada_tarde',
        'salida_tarde',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'horario_id');
    }

    /**
     * Devuelve el horario por defecto (General DRE).
     */
    public static function getDefault(): ?self
    {
        return static::where('activo', true)->oldest()->first();
    }

    /**
     * Resumen del horario en texto.
     */
    public function getResumenAttribute(): string
    {
        $man  = substr($this->entrada_manana, 0, 5) . ' - ' . substr($this->salida_manana, 0, 5);
        $tard = $this->entrada_tarde
            ? ' / ' . substr($this->entrada_tarde, 0, 5) . ' - ' . substr($this->salida_tarde, 0, 5)
            : '';
        return $man . $tard;
    }
}
