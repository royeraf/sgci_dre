<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoExpositor extends Model
{
    use HasUuids;

    protected $table = 'utilitario_evento_expositores';

    protected $fillable = [
        'evento_id',
        'nombre',
        'entidad',
        'orden',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }
}
