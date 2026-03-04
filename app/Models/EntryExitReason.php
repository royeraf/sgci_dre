<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EntryExitReason extends Model
{
    use HasUuids;

    protected $table = 'entry_exit_reasons';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTipo($query, string $tipo)
    {
        return $query->where(function ($q) use ($tipo) {
            $q->where('tipo', $tipo)->orWhere('tipo', 'ambos');
        });
    }
}
