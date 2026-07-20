<?php

namespace App\Http\Controllers;

use App\Events\AsistenciaAutomarcada;
use App\Models\Evento;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventoAsistenciaPublicaController extends Controller
{
    public function show(Evento $evento)
    {
        [$disponible, $motivo] = $this->estadoVentana($evento);

        return Inertia::render('Utilitarios/Asistencia/Publica', [
            'evento' => $this->presentarEvento($evento),
            'disponible' => $disponible,
            'motivo' => $motivo,
        ]);
    }

    public function marcar(Request $request, Evento $evento)
    {
        [$disponible, $motivo] = $this->estadoVentana($evento);

        if (!$disponible) {
            return response()->json(['message' => $motivo], 422);
        }

        $validated = $request->validate([
            'dni' => 'required|digits:8',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.digits' => 'El DNI debe tener exactamente 8 dígitos.',
        ]);

        $person = Person::findByDni($validated['dni']);
        $inscripcion = $person ? $evento->inscripciones()->where('person_id', $person->id)->first() : null;

        if (!$inscripcion) {
            return response()->json([
                'message' => 'No encontramos una inscripción con ese DNI para este evento.',
            ], 422);
        }

        $fecha = now()->toDateString();

        $asistencia = $inscripcion->asistencias()->firstOrCreate(
            ['fecha' => $fecha],
            ['marcado_en' => now(), 'marcado_por' => null]
        );

        if ($asistencia->wasRecentlyCreated) {
            event(new AsistenciaAutomarcada($inscripcion, $asistencia));
        }

        return response()->json([
            'message' => $asistencia->wasRecentlyCreated
                ? '¡Asistencia registrada! Gracias, ' . $person->nombres . '.'
                : 'Ya habías registrado tu asistencia hoy (' . $asistencia->marcado_en->format('H:i') . ').',
            'ya_marcada' => !$asistencia->wasRecentlyCreated,
            'nombres' => $person->nombres,
            'fecha' => $asistencia->fecha->format('Y-m-d'),
            'marcado_en' => $asistencia->marcado_en->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Autoridad única de la ventana de asistencia: el evento debe estar publicado,
     * hoy debe caer dentro del rango del evento, y la hora actual debe estar dentro
     * de hora_inicio/hora_fin (si el evento las define).
     *
     * @return array{0: bool, 1: ?string}
     */
    private function estadoVentana(Evento $evento): array
    {
        if ($evento->estado !== 'publicado') {
            return [false, 'Este evento no está disponible para el registro de asistencia.'];
        }

        $hoy = now()->toDateString();

        if ($hoy < $evento->fecha_inicio->format('Y-m-d') || $hoy > $evento->fecha_fin->format('Y-m-d')) {
            return [false, 'La asistencia solo puede marcarse durante los días del evento.'];
        }

        if ($evento->hora_inicio && $evento->hora_fin) {
            $inicio = Carbon::parse($hoy . ' ' . $evento->hora_inicio);
            $fin = Carbon::parse($hoy . ' ' . $evento->hora_fin);

            if (now()->lt($inicio) || now()->gt($fin)) {
                return [false, sprintf(
                    'El registro de asistencia está disponible solo durante el horario del evento (%s - %s).',
                    substr($evento->hora_inicio, 0, 5),
                    substr($evento->hora_fin, 0, 5)
                )];
            }
        }

        return [true, null];
    }

    private function presentarEvento(Evento $evento): array
    {
        return [
            'titulo' => $evento->titulo,
            'slug' => $evento->slug,
            'tipo' => $evento->tipo,
            'modalidad' => $evento->modalidad,
            'lugar' => $evento->lugar,
            'fecha_inicio' => $evento->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $evento->fecha_fin->format('Y-m-d'),
            'hora_inicio' => $evento->hora_inicio ? substr($evento->hora_inicio, 0, 5) : null,
            'hora_fin' => $evento->hora_fin ? substr($evento->hora_fin, 0, 5) : null,
            'color_primario' => $evento->color_primario,
            'imagen_fondo_url' => $evento->imagen_fondo_url,
        ];
    }
}
