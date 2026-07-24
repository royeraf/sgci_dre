<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\EventoInscripcion;
use App\Models\ExamenIntento;
use Barryvdh\DomPDF\Facade\Pdf;

class UtilitariosReporteController extends Controller
{
    public function inscritosPdf(string $evento)
    {
        $evento = Evento::with(['inscripciones.person', 'inscripciones.direction', 'inscripciones.office', 'inscripciones.contractType'])
            ->findOrFail($evento);

        $inscritos = $evento->inscripciones->map(fn (EventoInscripcion $inscripcion) => $inscripcion->toAdminArray());

        $pdf = Pdf::loadView('pdf.utilitarios_inscritos', [
            'evento' => $evento,
            'inscritos' => $inscritos,
            'total' => $inscritos->count(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('inscritos_' . $evento->slug . '.pdf');
    }

    public function asistenciaPdf(string $evento)
    {
        $evento = Evento::with(['inscripciones.person', 'inscripciones.asistencias'])->findOrFail($evento);

        $dias = $evento->diasEvento();

        $inscritos = $evento->inscripciones->map(function (EventoInscripcion $inscripcion) use ($dias) {
            $asistenciasPorFecha = $inscripcion->asistencias->keyBy(fn ($a) => $a->fecha->format('Y-m-d'));

            return [
                'nombres' => $inscripcion->nombres,
                'apellidos' => $inscripcion->apellidos,
                'numero_documento' => $inscripcion->numero_documento,
                'marcas' => collect($dias)->mapWithKeys(fn ($dia) => [$dia => $asistenciasPorFecha->has($dia)]),
                'total_asistencias' => $asistenciasPorFecha->count(),
            ];
        });

        $pdf = Pdf::loadView('pdf.utilitarios_asistencia', [
            'evento' => $evento,
            'dias' => $dias,
            'inscritos' => $inscritos,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('asistencia_' . $evento->slug . '.pdf');
    }

    public function resultadosPdf(string $evento, string $examen)
    {
        $evento = Evento::findOrFail($evento);
        $examen = $evento->examenes()->findOrFail($examen);

        $notaMinima = $examen->nota_minima_aprobatoria !== null ? (float) $examen->nota_minima_aprobatoria : null;

        $query = $examen->intentos()->with('inscripcion.person');
        $resumen = ExamenController::calcularResumenResultados((clone $query), $notaMinima);

        $intentos = $query->orderBy('iniciado_en', 'desc')
            ->get()
            ->map(fn (ExamenIntento $intento) => $intento->toResultadoArray());

        $pdf = Pdf::loadView('pdf.utilitarios_resultados', [
            'evento' => $evento,
            'examen' => $examen,
            'notaMinima' => $notaMinima,
            'resumen' => $resumen,
            'intentos' => $intentos,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('resultados_' . $examen->slug . '.pdf');
    }
}
