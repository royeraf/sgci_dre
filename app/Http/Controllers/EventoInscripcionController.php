<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\HRContractType;
use App\Models\HrDirection;
use App\Models\Person;
use App\Services\ReniecService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EventoInscripcionController extends Controller
{
    public function consultarDni(Request $request, ReniecService $reniecService)
    {
        $request->validate([
            'dni' => 'required|string|size:8',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 dígitos.',
        ]);

        $localPerson = Person::findByDni($request->dni);

        if ($localPerson) {
            return response()->json([
                'success' => true,
                'message' => 'Datos encontrados en registro local',
                'data' => [
                    'dni' => $localPerson->dni,
                    'nombres' => $localPerson->nombres,
                    'apellido_paterno' => $localPerson->apellidos,
                    'apellido_materno' => '',
                    'nombre_completo' => $localPerson->nombre_full,
                ]
            ]);
        }

        return response()->json($reniecService->consultarDni($request->dni));
    }

    public function show(Evento $evento)
    {
        [$disponible, $motivo] = $this->verificarDisponibilidad($evento);

        $evento->load('expositores');

        return Inertia::render('Utilitarios/Inscripcion/Show', [
            'evento' => $this->presentarEvento($evento),
            'disponible' => $disponible,
            'motivo' => $motivo,
            'regimenes' => HRContractType::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'directions' => HrDirection::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request, Evento $evento)
    {
        [$disponible, $motivo] = $this->verificarDisponibilidad($evento);

        if (!$disponible) {
            return response()->json(['message' => $motivo], 422);
        }

        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'genero' => 'nullable|in:Masculino,Femenino',
            'tipo_documento' => 'required|in:DNI,CE,Pasaporte',
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('utilitario_inscripciones', 'numero_documento')
                    ->where('evento_id', $evento->id),
            ],
            'correo' => 'required|email|max:150',
            'celular' => 'required|string|max:20',
            'institucion' => 'nullable|string|max:150',
            'direction_id' => 'required|exists:hr_directions,id',
            'cargo' => 'nullable|string|max:100',
            'profesion' => 'nullable|string|max:100',
            'contract_type_id' => 'required|exists:hr_contract_types,id',
        ], [
            'numero_documento.unique' => 'Este número de documento ya está inscrito en este evento.',
            'direction_id.required' => 'Debe seleccionar una dirección.',
            'contract_type_id.required' => 'Debe seleccionar un régimen.',
        ]);

        $inscripcion = DB::transaction(function () use ($evento, $validated) {
            $eventoBloqueado = Evento::where('id', $evento->id)->lockForUpdate()->first();

            if ($eventoBloqueado->cupo_maximo !== null
                && $eventoBloqueado->inscripciones()->count() >= $eventoBloqueado->cupo_maximo) {
                abort(response()->json(['message' => 'El cupo para este evento ya está lleno.'], 422));
            }

            return $eventoBloqueado->inscripciones()->create($validated);
        });

        return response()->json([
            'message' => 'Inscripción registrada correctamente',
            'id' => $inscripcion->id,
        ], 201);
    }

    private function verificarDisponibilidad(Evento $evento): array
    {
        if ($evento->estado !== 'publicado') {
            return [false, 'Este evento no está disponible para inscripciones.'];
        }

        if (now()->toDateString() > $evento->fecha_fin->toDateString()) {
            return [false, 'Las inscripciones para este evento ya finalizaron.'];
        }

        if ($evento->cupo_maximo !== null && $evento->inscripciones()->count() >= $evento->cupo_maximo) {
            return [false, 'El cupo para este evento ya está lleno.'];
        }

        return [true, null];
    }

    private function presentarEvento(Evento $evento): array
    {
        return [
            'titulo' => $evento->titulo,
            'slug' => $evento->slug,
            'tipo' => $evento->tipo,
            'descripcion' => $evento->descripcion,
            'modalidad' => $evento->modalidad,
            'lugar' => $evento->lugar,
            'enlace_virtual' => $evento->enlace_virtual,
            'fecha_inicio' => $evento->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $evento->fecha_fin->format('Y-m-d'),
            'hora_inicio' => $evento->hora_inicio,
            'hora_fin' => $evento->hora_fin,
            'cupo_maximo' => $evento->cupo_maximo,
            'horas_educativas' => $evento->horas_educativas,
            'color_primario' => $evento->color_primario,
            'imagen_fondo_url' => $evento->imagen_fondo_url,
            'inscritos_count' => $evento->inscripciones()->count(),
            'expositores' => $evento->expositores->map(fn ($e) => [
                'nombre' => $e->nombre,
                'entidad' => $e->entidad,
            ]),
        ];
    }
}
