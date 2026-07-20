<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenPregunta extends Model
{
    use HasUuids;

    protected $table = 'utilitario_examen_preguntas';

    protected $fillable = [
        'examen_id',
        'enunciado',
        'orden',
    ];

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'examen_id');
    }

    public function alternativas(): HasMany
    {
        return $this->hasMany(ExamenAlternativa::class, 'pregunta_id')->orderBy('orden');
    }
}
