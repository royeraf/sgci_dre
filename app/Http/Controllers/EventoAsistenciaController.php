<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\EventoInscripcion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventoAsistenciaController extends Controller
{
    public function index(string $evento)
    {
        $evento = Evento::with(['expositores', 'inscripciones.asistencias', 'inscripciones.contractType', 'inscripciones.direction'])->findOrFail($evento);

        return Inertia::render('Utilitarios/Inscritos', [
            'evento' => [
                'id' => $evento->id,
                'titulo' => $evento->titulo,
                'slug' => $evento->slug,
                'estado' => $evento->estado,
                'fecha_inicio' => $evento->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $evento->fecha_fin->format('Y-m-d'),
                'cupo_maximo' => $evento->cupo_maximo,
                'dias' => $evento->diasEvento(),
            ],
            'inscripciones' => $evento->inscripciones->map(fn (EventoInscripcion $inscripcion) => [
                'id' => $inscripcion->id,
                'nombres' => $inscripcion->nombres,
                'apellidos' => $inscripcion->apellidos,
                'genero' => $inscripcion->genero,
                'numero_documento' => $inscripcion->numero_documento,
                'correo' => $inscripcion->correo,
                'institucion' => $inscripcion->institucion,
                'direccion' => $inscripcion->direction?->nombre,
                'cargo' => $inscripcion->cargo,
                'profesion' => $inscripcion->profesion,
                'regimen' => $inscripcion->contractType?->nombre,
                'asistencias' => $inscripcion->asistencias->map(fn ($a) => [
                    'fecha' => $a->fecha->format('Y-m-d'),
                    'marcado_en' => $a->marcado_en?->format('Y-m-d H:i'),
                ]),
            ]),
        ]);
    }

    public function marcar(Request $request, string $evento, string $inscripcion)
    {
        $evento = Evento::findOrFail($evento);
        $inscripcion = EventoInscripcion::where('evento_id', $evento->id)->findOrFail($inscripcion);

        $validated = $request->validate([
            'fecha' => 'required|date',
        ]);

        $this->validarFechaEnRango($evento, $validated['fecha']);

        $asistencia = $inscripcion->asistencias()->firstOrCreate(
            ['fecha' => $validated['fecha']],
            ['marcado_en' => now(), 'marcado_por' => $request->user()->id]
        );

        return response()->json([
            'message' => 'Asistencia registrada correctamente',
            'fecha' => $asistencia->fecha->format('Y-m-d'),
            'marcado_en' => $asistencia->marcado_en->format('Y-m-d H:i'),
        ]);
    }

    public function desmarcar(Request $request, string $evento, string $inscripcion)
    {
        $evento = Evento::findOrFail($evento);
        $inscripcion = EventoInscripcion::where('evento_id', $evento->id)->findOrFail($inscripcion);

        $validated = $request->validate([
            'fecha' => 'required|date',
        ]);

        $inscripcion->asistencias()->where('fecha', $validated['fecha'])->delete();

        return response()->json(['message' => 'Asistencia removida correctamente']);
    }

    private function validarFechaEnRango(Evento $evento, string $fecha): void
    {
        if ($fecha < $evento->fecha_inicio->format('Y-m-d') || $fecha > $evento->fecha_fin->format('Y-m-d')) {
            abort(response()->json(['message' => 'La fecha está fuera del rango del evento.'], 422));
        }
    }
}
