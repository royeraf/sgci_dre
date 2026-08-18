<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\CarbonPeriod;

class Evento extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'utilitario_eventos';

    protected $fillable = [
        'titulo',
        'slug',
        'tipo',
        'descripcion',
        'modalidad',
        'lugar',
        'enlace_virtual',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'cupo_maximo',
        'horas_educativas',
        'color_primario',
        'imagen_fondo',
        'estado',
        'creado_por',
        'asistencia_habilitada',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'asistencia_habilitada' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Evento $evento) {
            if (empty($evento->slug)) {
                $evento->slug = static::generarSlugUnico($evento->titulo);
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

    public function getImagenFondoUrlAttribute(): string
    {
        return $this->imagen_fondo
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->imagen_fondo)
            : '/images/fondo_eventos.jpg';
    }

    public function diasEvento(): array
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return [];
        }

        return array_map(
            fn ($fecha) => $fecha->format('Y-m-d'),
            CarbonPeriod::create($this->fecha_inicio, $this->fecha_fin)->toArray()
        );
    }

    public function expositores(): HasMany
    {
        return $this->hasMany(EventoExpositor::class, 'evento_id')->orderBy('orden');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(EventoInscripcion::class, 'evento_id');
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class, 'evento_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
