<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Examen;
use App\Models\ExamenAlternativa;
use App\Models\ExamenIntento;
use App\Models\ExamenPregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExamenController extends Controller
{
    public function index(string $evento)
    {
        $evento = Evento::findOrFail($evento);
        $examenes = $evento->examenes()
            ->withCount('intentos')
            ->with('preguntas.alternativas')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Utilitarios/Examenes/Index', [
            'evento' => [
                'id' => $evento->id,
                'titulo' => $evento->titulo,
                'slug' => $evento->slug,
            ],
            'examenes' => $examenes->map(fn (Examen $examen) => $this->presentarExamen($examen, $evento->slug)),
        ]);
    }

    public function store(Request $request, string $evento)
    {
        $evento = Evento::findOrFail($evento);

        $validated = $this->validarExamen($request);
        $preguntas = $validated['preguntas'];
        unset($validated['preguntas']);

        $examen = $evento->examenes()->create([
            ...$validated,
            'creado_por' => $request->user()->id,
        ]);

        $this->sincronizarPreguntas($examen, $preguntas);

        return response()->json([
            'message' => 'Examen registrado correctamente',
            'id' => $examen->id,
        ], 201);
    }

    public function update(Request $request, string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);

        $validated = $this->validarExamen($request);
        $preguntas = $validated['preguntas'];
        unset($validated['preguntas']);

        // El slug no se regenera al editar, para no romper enlaces ya distribuidos.
        $examen->update($validated);

        // Si ya hay intentos registrados, no se resincronizan las preguntas: borrarlas
        // y recrearlas rompería la relación con las respuestas ya calificadas.
        $tieneIntentos = $examen->intentos()->exists();
        if (!$tieneIntentos) {
            $this->sincronizarPreguntas($examen, $preguntas);
        }

        return response()->json([
            'message' => $tieneIntentos
                ? 'Examen actualizado. Las preguntas no se modificaron porque el examen ya tiene intentos registrados.'
                : 'Examen actualizado correctamente',
        ]);
    }

    public function destroy(string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);
        $examen->delete();

        return response()->json(['message' => 'Examen eliminado correctamente']);
    }

    public function duplicar(Request $request, string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $original = $evento->examenes()->with('preguntas.alternativas')->findOrFail($examen);

        $copia = DB::transaction(function () use ($evento, $original, $request) {
            $copia = $evento->examenes()->create([
                'titulo' => 'Copia de ' . $original->titulo,
                'descripcion' => $original->descripcion,
                'fecha_inicio' => $original->fecha_inicio,
                'fecha_fin' => $original->fecha_fin,
                'hora_inicio' => $original->hora_inicio,
                'hora_fin' => $original->hora_fin,
                'duracion_minutos' => $original->duracion_minutos,
                'intentos_permitidos' => $original->intentos_permitidos,
                'nota_minima_aprobatoria' => $original->nota_minima_aprobatoria,
                'estado' => 'borrador',
                'examen_habilitado' => false,
                'creado_por' => $request->user()->id,
            ]);

            foreach ($original->preguntas as $pregunta) {
                $nuevaPregunta = $copia->preguntas()->create([
                    'enunciado' => $pregunta->enunciado,
                    'orden' => $pregunta->orden,
                ]);

                foreach ($pregunta->alternativas as $alternativa) {
                    $nuevaPregunta->alternativas()->create([
                        'texto' => $alternativa->texto,
                        'es_correcta' => $alternativa->es_correcta,
                        'orden' => $alternativa->orden,
                    ]);
                }
            }

            return $copia;
        });

        return response()->json([
            'message' => 'Examen duplicado correctamente',
            'id' => $copia->id,
        ], 201);
    }

    public function cambiarEstado(Request $request, string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);

        $validated = $request->validate([
            'estado' => 'required|in:borrador,publicado,cerrado',
        ]);

        $examen->update($validated);

        return response()->json(['message' => 'Estado del examen actualizado correctamente']);
    }

    public function toggleHabilitado(Request $request, string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);

        $validated = $request->validate([
            'habilitado' => 'required|boolean',
        ]);

        $examen->update(['examen_habilitado' => $validated['habilitado']]);

        return response()->json([
            'message' => $validated['habilitado'] ? 'Enlace de examen activado' : 'Enlace de examen desactivado',
            'examen_habilitado' => $examen->examen_habilitado,
        ]);
    }

    public function resultados(Request $request, string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);
        $notaMinima = $examen->nota_minima_aprobatoria !== null ? (float) $examen->nota_minima_aprobatoria : null;

        $query = $examen->intentos()->with('inscripcion.person');
        $this->aplicarFiltrosResultados($query, $request, $notaMinima);

        $resumen = self::calcularResumenResultados((clone $query), $notaMinima);

        $intentos = $query->orderBy('iniciado_en', 'desc')
            ->paginate($request->input('per_page', 15))
            ->through(fn (ExamenIntento $intento) => $intento->toResultadoArray())
            ->withQueryString();

        return Inertia::render('Utilitarios/Examenes/Resultados', [
            'evento' => [
                'id' => $evento->id,
                'titulo' => $evento->titulo,
                'slug' => $evento->slug,
            ],
            'examen' => [
                'id' => $examen->id,
                'titulo' => $examen->titulo,
                'nota_minima_aprobatoria' => $notaMinima,
            ],
            'intentos' => $intentos,
            'resumen' => $resumen,
            'filtros' => $request->only(['search', 'resultado', 'fecha_desde', 'fecha_hasta']),
        ]);
    }

    public function detalleIntento(string $evento, string $examen, string $intento)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);
        $intento = $examen->intentos()->with(['inscripcion', 'respuestas'])->findOrFail($intento);

        $preguntas = $examen->preguntas()->with('alternativas')->orderBy('orden')->get();
        $respuestasPorPregunta = $intento->respuestas->keyBy('pregunta_id');

        return response()->json([
            'participante' => [
                'nombres' => $intento->inscripcion?->nombres,
                'apellidos' => $intento->inscripcion?->apellidos,
                'numero_documento' => $intento->inscripcion?->numero_documento,
            ],
            'numero_intento' => $intento->numero_intento,
            'puntaje' => $intento->puntaje !== null ? (float) $intento->puntaje : null,
            'aciertos' => $intento->aciertos,
            'total_preguntas' => $intento->total_preguntas,
            'finalizado_en' => $intento->finalizado_en?->format('Y-m-d H:i'),
            'preguntas' => $preguntas->map(function (ExamenPregunta $pregunta) use ($respuestasPorPregunta) {
                $respuesta = $respuestasPorPregunta->get($pregunta->id);

                return [
                    'id' => $pregunta->id,
                    'enunciado' => $pregunta->enunciado,
                    'alternativa_marcada_id' => $respuesta?->alternativa_id,
                    'respondida' => $respuesta !== null && $respuesta->alternativa_id !== null,
                    'es_correcta' => $respuesta?->es_correcta,
                    'alternativas' => $pregunta->alternativas->map(fn (ExamenAlternativa $alternativa) => [
                        'id' => $alternativa->id,
                        'texto' => $alternativa->texto,
                        'es_correcta' => $alternativa->es_correcta,
                    ]),
                ];
            }),
        ]);
    }

    private function aplicarFiltrosResultados($query, Request $request, ?float $notaMinima): void
    {
        if ($search = $request->input('search')) {
            $query->whereHas('inscripcion.person', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        switch ($request->input('resultado')) {
            case 'en_curso':
                $query->whereNull('finalizado_en');
                break;
            case 'finalizado':
                $query->whereNotNull('finalizado_en');
                break;
            case 'aprobado':
                $query->whereNotNull('finalizado_en');
                if ($notaMinima !== null) {
                    $query->where('puntaje', '>=', $notaMinima);
                }
                break;
            case 'desaprobado':
                $query->whereNotNull('finalizado_en');
                if ($notaMinima !== null) {
                    $query->where('puntaje', '<', $notaMinima);
                } else {
                    // Sin nota mínima configurada no se puede determinar desaprobados.
                    $query->whereRaw('1 = 0');
                }
                break;
        }

        if ($fechaDesde = $request->input('fecha_desde')) {
            $query->whereDate('iniciado_en', '>=', $fechaDesde);
        }

        if ($fechaHasta = $request->input('fecha_hasta')) {
            $query->whereDate('iniciado_en', '<=', $fechaHasta);
        }
    }

    /**
     * Calcula el resumen agregado (total, finalizados, aprobados, tasa, promedio) de un
     * query de intentos. Se expone como público/estático para poder reutilizarlo desde
     * UtilitariosReporteController al generar el PDF de resultados.
     */
    public static function calcularResumenResultados($query, ?float $notaMinima): array
    {
        $total = (clone $query)->count();
        $finalizados = (clone $query)->whereNotNull('finalizado_en')->count();
        $enCurso = $total - $finalizados;

        $aprobados = null;
        $desaprobados = null;
        $tasaAprobacion = null;

        if ($notaMinima !== null) {
            $aprobados = (clone $query)->whereNotNull('finalizado_en')->where('puntaje', '>=', $notaMinima)->count();
            $desaprobados = $finalizados - $aprobados;
            $tasaAprobacion = $finalizados > 0 ? round($aprobados / $finalizados * 100, 1) : null;
        }

        $promedioNota = (clone $query)->whereNotNull('finalizado_en')->avg('puntaje');

        return [
            'total' => $total,
            'finalizados' => $finalizados,
            'en_curso' => $enCurso,
            'aprobados' => $aprobados,
            'desaprobados' => $desaprobados,
            'tasa_aprobacion' => $tasaAprobacion,
            'promedio_nota' => $promedioNota !== null ? round((float) $promedioNota, 2) : null,
        ];
    }

    private function validarExamen(Request $request): array
    {
        // Los valores de hora pueden llegar con segundos (ej. "09:00:00"); se
        // normalizan a H:i, igual que en EventoController::validarEvento().
        foreach (['hora_inicio', 'hora_fin'] as $campo) {
            if (is_string($request->input($campo)) && strlen($request->input($campo)) > 5) {
                $request->merge([$campo => substr($request->input($campo), 0, 5)]);
            }
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string|max:5000',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'duracion_minutos' => 'required|integer|min:1|max:600',
            'intentos_permitidos' => 'required|integer|min:1|max:20',
            'nota_minima_aprobatoria' => 'nullable|numeric|min:0|max:20',
            'estado' => 'nullable|in:borrador,publicado,cerrado',
            'preguntas' => 'required|array|min:1',
            'preguntas.*.enunciado' => 'required|string|max:2000',
            'preguntas.*.alternativas' => 'required|array|min:2',
            'preguntas.*.alternativas.*.texto' => 'required|string|max:500',
            'preguntas.*.alternativas.*.es_correcta' => 'required|boolean',
        ]);

        foreach ($validated['preguntas'] as $index => $pregunta) {
            $correctas = collect($pregunta['alternativas'])->where('es_correcta', true)->count();
            if ($correctas !== 1) {
                abort(response()->json([
                    'message' => sprintf('La pregunta %d debe tener exactamente una alternativa marcada como correcta.', $index + 1),
                ], 422));
            }
        }

        return $validated;
    }

    private function sincronizarPreguntas(Examen $examen, array $preguntas): void
    {
        $examen->preguntas()->delete();

        foreach (array_values($preguntas) as $ordenPregunta => $pregunta) {
            $preguntaModel = $examen->preguntas()->create([
                'enunciado' => $pregunta['enunciado'],
                'orden' => $ordenPregunta,
            ]);

            foreach (array_values($pregunta['alternativas']) as $ordenAlternativa => $alternativa) {
                $preguntaModel->alternativas()->create([
                    'texto' => $alternativa['texto'],
                    'es_correcta' => (bool) $alternativa['es_correcta'],
                    'orden' => $ordenAlternativa,
                ]);
            }
        }
    }

    private function presentarExamen(Examen $examen, string $eventoSlug): array
    {
        return [
            'id' => $examen->id,
            'titulo' => $examen->titulo,
            'slug' => $examen->slug,
            'descripcion' => $examen->descripcion,
            'fecha_inicio' => $examen->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $examen->fecha_fin->format('Y-m-d'),
            'hora_inicio' => $examen->hora_inicio ? substr($examen->hora_inicio, 0, 5) : null,
            'hora_fin' => $examen->hora_fin ? substr($examen->hora_fin, 0, 5) : null,
            'duracion_minutos' => $examen->duracion_minutos,
            'intentos_permitidos' => $examen->intentos_permitidos,
            'nota_minima_aprobatoria' => $examen->nota_minima_aprobatoria !== null ? (float) $examen->nota_minima_aprobatoria : null,
            'estado' => $examen->estado,
            'examen_habilitado' => $examen->examen_habilitado,
            'intentos_count' => $examen->intentos_count,
            'preguntas' => $examen->preguntas->map(fn (ExamenPregunta $pregunta) => [
                'id' => $pregunta->id,
                'enunciado' => $pregunta->enunciado,
                'alternativas' => $pregunta->alternativas->map(fn (ExamenAlternativa $alternativa) => [
                    'id' => $alternativa->id,
                    'texto' => $alternativa->texto,
                    'es_correcta' => $alternativa->es_correcta,
                ]),
            ]),
            'enlace_publico' => url('/utilitarios/examen/' . $eventoSlug . '/' . $examen->slug),
        ];
    }
}
