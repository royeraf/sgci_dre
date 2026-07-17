<?php

namespace App\Http\Controllers;

use App\Mail\InscripcionConfirmada;
use App\Models\Evento;
use App\Models\EventoInscripcion;
use App\Models\HRContractType;
use App\Models\HrDirection;
use App\Models\HrOffice;
use App\Models\Person;
use App\Services\ReniecService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventoInscripcionController extends Controller
{
    public function consultarDni(Request $request, ReniecService $reniecService)
    {
        $request->validate([
            'dni' => 'required|digits:8',
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.digits' => 'El DNI debe tener exactamente 8 dígitos.',
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
            'offices' => HrOffice::where('activo', true)->with('direction:id,nombre')->orderBy('nombre')->get(['id', 'direction_id', 'nombre']),
        ]);
    }

    public function store(Request $request, Evento $evento)
    {
        [$disponible, $motivo] = $this->verificarDisponibilidad($evento);

        if (!$disponible) {
            return response()->json(['message' => $motivo], 422);
        }

        // El celular se escribe con espacios en el formulario (placeholder "999 999 999");
        // se normaliza antes de validar el formato para no rechazar envíos legítimos.
        if (is_string($request->input('celular'))) {
            $request->merge(['celular' => preg_replace('/\D/', '', $request->input('celular'))]);
        }

        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'genero' => 'required|in:Masculino,Femenino',
            'numero_documento' => ['required', 'digits:8'],
            'correo' => 'required|email|max:150',
            'celular' => 'required|digits:9',
            'direction_id' => 'required|exists:hr_directions,id',
            'office_id' => 'required|exists:hr_offices,id',
            'cargo' => 'required|string|max:100',
            'profesion' => 'required|string|max:100',
            'contract_type_id' => 'required|exists:hr_contract_types,id',
        ], [
            'numero_documento.digits' => 'El DNI debe tener exactamente 8 dígitos.',
            'celular.digits' => 'El celular debe tener exactamente 9 dígitos.',
            'genero.required' => 'Debe seleccionar un género.',
            'direction_id.required' => 'Debe seleccionar una dirección.',
            'office_id.required' => 'Debe seleccionar una oficina.',
            'cargo.required' => 'El cargo es obligatorio.',
            'profesion.required' => 'La profesión es obligatoria.',
            'contract_type_id.required' => 'Debe seleccionar un régimen.',
        ]);

        $inscripcion = DB::transaction(function () use ($evento, $validated) {
            $eventoBloqueado = Evento::where('id', $evento->id)->lockForUpdate()->first();

            if ($eventoBloqueado->cupo_maximo !== null
                && $eventoBloqueado->inscripciones()->count() >= $eventoBloqueado->cupo_maximo) {
                abort(response()->json(['message' => 'El cupo para este evento ya está lleno.'], 422));
            }

            // Buscar o crear la persona en people (solo se sobrescriben sus datos si no es un empleado interno,
            // para no pisar datos de RRHH). El correo/celular de la inscripción NO se guarda en people.email ni
            // people.telefono: ese campo es compartido con RRHH y con el login de usuarios del sistema
            // (User::getEmailAttribute), y una persona puede usar un correo distinto para inscribirse a cursos.
            $person = Person::firstOrNew(['dni' => $validated['numero_documento']]);

            if ($person->tipo !== 'INTERNO') {
                $person->nombres = Str::upper($validated['nombres']);
                $person->apellidos = Str::upper($validated['apellidos']);
                $person->tipo = 'EXTERNO';
                $person->is_active = true;
                $person->save();
            }

            if ($eventoBloqueado->inscripciones()->where('person_id', $person->id)->exists()) {
                abort(response()->json(['message' => 'Ya se encuentra inscrito a este evento con este número de documento.'], 422));
            }

            $datosInscripcion = collect($validated)
                ->except(['nombres', 'apellidos', 'numero_documento'])
                ->merge(['person_id' => $person->id])
                ->toArray();

            return $eventoBloqueado->inscripciones()->create($datosInscripcion);
        });

        $emailEnviado = $this->enviarCorreoConfirmacion($inscripcion);

        return response()->json([
            'message' => 'Inscripción registrada correctamente',
            'id' => $inscripcion->id,
            'email_enviado' => $emailEnviado,
        ], 201);
    }

    private function enviarCorreoConfirmacion(EventoInscripcion $inscripcion): bool
    {
        $inscripcion->load(['person', 'evento', 'direction', 'office', 'contractType']);

        try {
            Mail::to($inscripcion->correo)->send(new InscripcionConfirmada($inscripcion));

            return true;
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de confirmación de inscripción', [
                'inscripcion_id' => $inscripcion->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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
