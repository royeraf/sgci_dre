<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenRespuesta extends Model
{
    use HasUuids;

    protected $table = 'utilitario_examen_respuestas';

    protected $fillable = [
        'intento_id',
        'pregunta_id',
        'alternativa_id',
        'es_correcta',
    ];

    protected $casts = [
        'es_correcta' => 'boolean',
    ];

    public function intento(): BelongsTo
    {
        return $this->belongsTo(ExamenIntento::class, 'intento_id');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(ExamenPregunta::class, 'pregunta_id');
    }

    public function alternativa(): BelongsTo
    {
        return $this->belongsTo(ExamenAlternativa::class, 'alternativa_id');
    }
}
