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
        'person_id',
        'genero',
        'institucion',
        'correo',
        'celular',
        'direction_id',
        'office_id',
        'cargo',
        'profesion',
        'contract_type_id',
    ];

    protected $appends = [
        'nombres',
        'apellidos',
        'numero_documento',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
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

    public function office(): BelongsTo
    {
        return $this->belongsTo(HrOffice::class, 'office_id');
    }

    public function getNombresAttribute(): ?string
    {
        return $this->person?->nombres;
    }

    public function getApellidosAttribute(): ?string
    {
        return $this->person?->apellidos;
    }

    public function getNumeroDocumentoAttribute(): ?string
    {
        return $this->person?->dni;
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'evento_id' => $this->evento_id,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'genero' => $this->genero,
            'numero_documento' => $this->numero_documento,
            'correo' => $this->correo,
            'direccion' => $this->direction?->nombre,
            'oficina' => $this->office?->nombre,
            'cargo' => $this->cargo,
            'profesion' => $this->profesion,
            'regimen' => $this->contractType?->nombre,
            'asistencias' => $this->asistencias->map(fn ($a) => [
                'fecha' => $a->fecha->format('Y-m-d'),
                'marcado_en' => $a->marcado_en?->format('Y-m-d H:i'),
                'auto' => $a->marcado_por === null,
            ]),
        ];
    }
}
