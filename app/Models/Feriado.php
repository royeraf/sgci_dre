<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feriado extends Model
{
    protected $table = 'feriados';

    protected $fillable = ['fecha', 'nombre', 'tipo', 'creado_por'];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function scopeDelAnio($query, int $year)
    {
        return $query->whereYear('fecha', $year);
    }
}
