<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Examen extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'utilitario_examenes';

    protected $fillable = [
        'evento_id',
        'titulo',
        'slug',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'duracion_minutos',
        'intentos_permitidos',
        'nota_minima_aprobatoria',
        'estado',
        'examen_habilitado',
        'creado_por',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'examen_habilitado' => 'boolean',
        'nota_minima_aprobatoria' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Examen $examen) {
            if (empty($examen->slug)) {
                $examen->slug = static::generarSlugUnico($examen->titulo);
            }
        });
    }

    public static function generarSlugUnico(string $titulo): string
    {
        $base = Str::slug($titulo);

        do {
            $slug = $base . '-' . Str::lower(Str::random(6));
        } while (static::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class, 'evento_id');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(ExamenPregunta::class, 'examen_id')->orderBy('orden');
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(ExamenIntento::class, 'examen_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
