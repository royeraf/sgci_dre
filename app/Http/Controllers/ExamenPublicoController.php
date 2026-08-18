<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Examen;
use App\Models\ExamenAlternativa;
use App\Models\ExamenIntento;
use App\Models\ExamenPregunta;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExamenPublicoController extends Controller
{
    public function show(Evento $evento, Examen $examen)
    {
        abort_unless($examen->evento_id === $evento->id, 404);

        [$disponible, $motivo] = $this->estadoVentana($evento, $examen);

        return Inertia::render('Utilitarios/Examen/Show', [
            'evento' => $this->presentarEventoPublico($evento),
            'examen' => $this->presentarExamenPublico($examen),
            'disponible' => $disponible,
            'motivo' => $motivo,
        ]);
    }

    public function iniciar(Request $request, Evento $evento, Examen $examen)
    {
        abort_unless($examen->evento_id === $evento->id, 404);

        [$disponible, $motivo] = $this->estadoVentana($evento, $examen);

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

        $intento = DB::transaction(function () use ($examen, $inscripcion) {
            // Bloquea la fila del examen para serializar intentos concurrentes de la
            // misma persona (mismo criterio que el control de cupo en inscripciones).
            $examenBloqueado = Examen::where('id', $examen->id)->lockForUpdate()->first();

            $intentoAbierto = $examenBloqueado->intentos()
                ->where('inscripcion_id', $inscripcion->id)
                ->whereNull('finalizado_en')
                ->latest('numero_intento')
                ->first();

            // Un intento abierto siempre se reanuda: el participante vuelve a su mismo
            // intento con el tiempo restante. Si el tiempo ya venció, rendir() lo
            // califica y muestra el resultado (auto-grade). Nunca se bloquea un
            // intento en curso ni se cuenta contra el límite de intentos permitidos.
            if ($intentoAbierto) {
                return $intentoAbierto;
            }

            $intentosUsados = $examenBloqueado->intentos()
                ->where('inscripcion_id', $inscripcion->id)
                ->whereNotNull('finalizado_en')
                ->count();

            if ($intentosUsados >= $examenBloqueado->intentos_permitidos) {
                abort(response()->json([
                    'message' => 'Ya utilizaste todos tus intentos permitidos para este examen.',
                ], 422));
            }

            return $examenBloqueado->intentos()->create([
                'inscripcion_id' => $inscripcion->id,
                'numero_intento' => $intentosUsados + 1,
                'iniciado_en' => now(),
            ]);
        });

        return response()->json(['intento_id' => $intento->id]);
    }

    public function rendir(Evento $evento, Examen $examen, ExamenIntento $intento)
    {
        abort_unless($examen->evento_id === $evento->id, 404);
        abort_unless($intento->examen_id === $examen->id, 404);

        if ($intento->finalizado_en === null && now()->gte($this->limiteIntento($intento, $examen))) {
            $this->calificarIntento($intento);
            $intento->refresh();
        }

        if ($intento->finalizado_en !== null) {
            return Inertia::render('Utilitarios/Examen/Rendir', [
                'evento' => $this->presentarEventoPublico($evento),
                'examen' => $this->presentarExamenPublico($examen),
                'finalizado' => true,
                'resultado' => $this->presentarResultado($examen, $intento),
            ]);
        }

        return Inertia::render('Utilitarios/Examen/Rendir', [
            'evento' => $this->presentarEventoPublico($evento),
            'examen' => $this->presentarExamenPublico($examen),
            'finalizado' => false,
            'intento_id' => $intento->id,
            'segundos_restantes' => max(0, $this->limiteIntento($intento, $examen)->timestamp - now()->timestamp),
            'preguntas' => $this->prepararPreguntas($examen, $intento),
            'respuestas_guardadas' => $intento->respuestas()->pluck('alternativa_id', 'pregunta_id'),
        ]);
    }

    public function responder(Request $request, Evento $evento, Examen $examen, ExamenIntento $intento)
    {
        abort_unless($examen->evento_id === $evento->id, 404);
        abort_unless($intento->examen_id === $examen->id, 404);

        if ($intento->finalizado_en !== null) {
            return response()->json(['message' => 'Este intento ya fue finalizado.'], 422);
        }

        if (now()->gte($this->limiteIntento($intento, $examen))) {
            $this->calificarIntento($intento);
            return response()->json(['message' => 'El tiempo para este examen ya terminó.'], 422);
        }

        $validated = $request->validate([
            'pregunta_id' => ['required', 'uuid', Rule::exists('utilitario_examen_preguntas', 'id')->where('examen_id', $examen->id)],
            'alternativa_id' => ['required', 'uuid', Rule::exists('utilitario_examen_alternativas', 'id')],
        ]);

        $alternativaValida = ExamenAlternativa::where('id', $validated['alternativa_id'])
            ->where('pregunta_id', $validated['pregunta_id'])
            ->exists();

        if (!$alternativaValida) {
            return response()->json(['message' => 'La alternativa no corresponde a la pregunta indicada.'], 422);
        }

        $intento->respuestas()->updateOrCreate(
            ['pregunta_id' => $validated['pregunta_id']],
            ['alternativa_id' => $validated['alternativa_id']]
        );

        return response()->json(['message' => 'Respuesta guardada']);
    }

    public function finalizar(Evento $evento, Examen $examen, ExamenIntento $intento)
    {
        abort_unless($examen->evento_id === $evento->id, 404);
        abort_unless($intento->examen_id === $examen->id, 404);

        if ($intento->finalizado_en === null) {
            $this->calificarIntento($intento);
            $intento->refresh();
        }

        return response()->json($this->presentarResultado($examen, $intento));
    }

    /**
     * Autoridad única de la ventana de rendición: el evento y el examen deben
     * estar publicados, hoy debe caer dentro del rango del examen, la hora actual
     * debe estar dentro de hora_inicio/hora_fin (si se definieron), y el organizador
     * debe haber activado el interruptor manual `examen_habilitado`.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function estadoVentana(Evento $evento, Examen $examen): array
    {
        if ($evento->estado !== 'publicado') {
            return [false, 'Este evento no está disponible.'];
        }

        if ($examen->estado !== 'publicado') {
            return [false, 'Este examen no está disponible.'];
        }

        $hoy = now()->toDateString();

        if ($hoy < $examen->fecha_inicio->format('Y-m-d') || $hoy > $examen->fecha_fin->format('Y-m-d')) {
            return [false, 'El examen solo puede rendirse durante los días programados.'];
        }

        if ($examen->hora_inicio && $examen->hora_fin) {
            $inicio = Carbon::parse($hoy . ' ' . $examen->hora_inicio);
            $fin = Carbon::parse($hoy . ' ' . $examen->hora_fin);

            if (now()->lt($inicio) || now()->gt($fin)) {
                return [false, sprintf(
                    'El examen está disponible solo durante el horario programado (%s - %s).',
                    substr($examen->hora_inicio, 0, 5),
                    substr($examen->hora_fin, 0, 5)
                )];
            }
        }

        if (!$examen->examen_habilitado) {
            return [false, 'El examen aún no ha sido habilitado por el organizador.'];
        }

        return [true, null];
    }

    private function limiteIntento(ExamenIntento $intento, Examen $examen): Carbon
    {
        return $intento->iniciado_en->copy()->addMinutes($examen->duracion_minutos);
    }

    /**
     * Deriva un orden de preguntas/alternativas distinto por participante sin
     * persistir ninguna columna extra: la semilla es el propio id del intento, así
     * que una recarga de página siempre reproduce el mismo orden para ese intento.
     */
    private function prepararPreguntas(Examen $examen, ExamenIntento $intento): array
    {
        $preguntas = $examen->preguntas()->with('alternativas')->orderBy('orden')->get();

        mt_srand(crc32($intento->id));

        $preparadas = $preguntas->map(function (ExamenPregunta $pregunta) {
            $alternativas = $pregunta->alternativas->map(fn (ExamenAlternativa $alternativa) => [
                'id' => $alternativa->id,
                'texto' => $alternativa->texto,
            ])->all();
            shuffle($alternativas);

            return [
                'id' => $pregunta->id,
                'enunciado' => $pregunta->enunciado,
                'alternativas' => $alternativas,
            ];
        })->all();
        shuffle($preparadas);

        mt_srand(); // Se re-siembra al azar para no afectar aleatoriedad de otro código en este request.

        return $preparadas;
    }

    private function calificarIntento(ExamenIntento $intento): void
    {
        if ($intento->finalizado_en !== null) {
            return;
        }

        $preguntas = $intento->examen->preguntas()->with('alternativas')->get();
        $totalPreguntas = $preguntas->count();
        $respuestas = $intento->respuestas()->get()->keyBy('pregunta_id');
        $aciertos = 0;

        foreach ($preguntas as $pregunta) {
            $respuesta = $respuestas->get($pregunta->id);
            if (!$respuesta || !$respuesta->alternativa_id) {
                continue;
            }

            $alternativaCorrecta = $pregunta->alternativas->firstWhere('es_correcta', true);
            $esCorrecta = $alternativaCorrecta && $alternativaCorrecta->id === $respuesta->alternativa_id;

            if ($esCorrecta) {
                $aciertos++;
            }
            if ($respuesta->es_correcta !== $esCorrecta) {
                $respuesta->update(['es_correcta' => $esCorrecta]);
            }
        }

        $puntaje = $totalPreguntas > 0 ? round(($aciertos / $totalPreguntas) * 20, 2) : 0;

        $intento->update([
            'finalizado_en' => now(),
            'aciertos' => $aciertos,
            'total_preguntas' => $totalPreguntas,
            'puntaje' => $puntaje,
        ]);
    }

    private function presentarResultado(Examen $examen, ExamenIntento $intento): array
    {
        return [
            'aciertos' => $intento->aciertos,
            'total_preguntas' => $intento->total_preguntas,
            'puntaje' => (float) $intento->puntaje,
            'aprobado' => $examen->nota_minima_aprobatoria !== null
                ? (float) $intento->puntaje >= (float) $examen->nota_minima_aprobatoria
                : null,
        ];
    }

    private function presentarEventoPublico(Evento $evento): array
    {
        return [
            'titulo' => $evento->titulo,
            'slug' => $evento->slug,
            'color_primario' => $evento->color_primario,
            'imagen_fondo_url' => $evento->imagen_fondo_url,
        ];
    }

    private function presentarExamenPublico(Examen $examen): array
    {
        return [
            'titulo' => $examen->titulo,
            'slug' => $examen->slug,
            'descripcion' => $examen->descripcion,
            'duracion_minutos' => $examen->duracion_minutos,
            'fecha_inicio' => $examen->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $examen->fecha_fin->format('Y-m-d'),
            'hora_inicio' => $examen->hora_inicio ? substr($examen->hora_inicio, 0, 5) : null,
            'hora_fin' => $examen->hora_fin ? substr($examen->hora_fin, 0, 5) : null,
        ];
    }
}
