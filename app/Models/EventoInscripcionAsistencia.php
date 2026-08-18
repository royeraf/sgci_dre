<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoInscripcionAsistencia extends Model
{
    use HasUuids;

    protected $table = 'utilitario_inscripcion_asistencias';

    protected $fillable = [
        'inscripcion_id',
        'fecha',
        'marcado_en',
        'marcado_por',
    ];

    protected $casts = [
        'fecha' => 'date',
        'marcado_en' => 'datetime',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(EventoInscripcion::class, 'inscripcion_id');
    }

    public function marcadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marcado_por');
    }
}
