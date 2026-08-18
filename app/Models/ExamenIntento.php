<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenIntento extends Model
{
    use HasUuids;

    protected $table = 'utilitario_examen_intentos';

    protected $fillable = [
        'examen_id',
        'inscripcion_id',
        'numero_intento',
        'iniciado_en',
        'finalizado_en',
        'aciertos',
        'total_preguntas',
        'puntaje',
    ];

    protected $casts = [
        'iniciado_en' => 'datetime',
        'finalizado_en' => 'datetime',
        'puntaje' => 'decimal:2',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(EventoInscripcion::class, 'inscripcion_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(ExamenRespuesta::class, 'intento_id');
    }

    public function toResultadoArray(): array
    {
        $notaMinima = $this->examen?->nota_minima_aprobatoria;

        return [
            'id' => $this->id,
            'nombres' => $this->inscripcion?->nombres,
            'apellidos' => $this->inscripcion?->apellidos,
            'numero_documento' => $this->inscripcion?->numero_documento,
            'numero_intento' => $this->numero_intento,
            'aciertos' => $this->aciertos,
            'total_preguntas' => $this->total_preguntas,
            'puntaje' => $this->puntaje !== null ? (float) $this->puntaje : null,
            'aprobado' => $notaMinima !== null && $this->puntaje !== null
                ? (float) $this->puntaje >= (float) $notaMinima
                : null,
            'iniciado_en' => $this->iniciado_en?->format('Y-m-d H:i'),
            'finalizado_en' => $this->finalizado_en?->format('Y-m-d H:i'),
        ];
    }
}
