<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventoInscripcion extends Model
{
    use HasUuids;

    protected $table = 'utilitario_inscripciones';

    protected $fillable = [
        'evento_id',
        'nombres',
        'apellidos',
        'genero',
        'tipo_documento',
        'numero_documento',
        'correo',
        'celular',
        'institucion',
        'direction_id',
        'cargo',
        'profesion',
        'contract_type_id',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(EventoInscripcionAsistencia::class, 'inscripcion_id');
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(HRContractType::class, 'contract_type_id');
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(HrDirection::class, 'direction_id');
    }
}
