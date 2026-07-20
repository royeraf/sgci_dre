<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventoController extends Controller
{
    public function index()
    {
        return Inertia::render('Utilitarios/Index');
    }

    public function getEventos()
    {
        $eventos = Evento::withCount('inscripciones')
            ->with('expositores')
            ->orderBy('fecha_inicio', 'desc')
            ->get()
            ->map(function (Evento $evento) {
                return [
                    'id' => $evento->id,
                    'titulo' => $evento->titulo,
                    'slug' => $evento->slug,
                    'tipo' => $evento->tipo,
                    'descripcion' => $evento->descripcion,
                    'modalidad' => $evento->modalidad,
                    'lugar' => $evento->lugar,
                    'enlace_virtual' => $evento->enlace_virtual,
                    'fecha_inicio' => $evento->fecha_inicio->format('Y-m-d'),
                    'fecha_fin' => $evento->fecha_fin->format('Y-m-d'),
                    'hora_inicio' => $evento->hora_inicio ? substr($evento->hora_inicio, 0, 5) : null,
                    'hora_fin' => $evento->hora_fin ? substr($evento->hora_fin, 0, 5) : null,
                    'cupo_maximo' => $evento->cupo_maximo,
                    'horas_educativas' => $evento->horas_educativas,
                    'color_primario' => $evento->color_primario,
                    'imagen_fondo_url' => $evento->imagen_fondo_url,
                    'estado' => $evento->estado,
                    'inscritos_count' => $evento->inscripciones_count,
                    'expositores' => $evento->expositores->map(fn ($e) => [
                        'id' => $e->id,
                        'nombre' => $e->nombre,
                        'entidad' => $e->entidad,
                    ]),
                    'enlace_publico' => url('/utilitarios/inscripcion/' . $evento->slug),
                    'enlace_asistencia' => url('/utilitarios/asistencia/' . $evento->slug),
                ];
            });

        return response()->json($eventos);
    }

    public function store(Request $request)
    {
        $validated = $this->validarEvento($request);
        $expositores = $validated['expositores'];
        unset($validated['expositores'], $validated['imagen_fondo']);

        if ($request->hasFile('imagen_fondo')) {
            $validated['imagen_fondo'] = $request->file('imagen_fondo')->store('eventos', 'public');
        }

        $evento = Evento::create([
            ...$validated,
            'creado_por' => $request->user()->id,
        ]);

        $this->sincronizarExpositores($evento, $expositores);

        return response()->json([
            'message' => 'Evento registrado correctamente',
            'id' => $evento->id,
        ], 201);
    }

    public function update(Request $request, string $evento)
    {
        $evento = Evento::findOrFail($evento);

        $validated = $this->validarEvento($request);
        $expositores = $validated['expositores'];
        unset($validated['expositores'], $validated['imagen_fondo']);

        if ($request->hasFile('imagen_fondo')) {
            if ($evento->imagen_fondo) {
                Storage::disk('public')->delete($evento->imagen_fondo);
            }
            $validated['imagen_fondo'] = $request->file('imagen_fondo')->store('eventos', 'public');
        }

        // El slug no se regenera al editar, para no romper enlaces ya distribuidos.
        $evento->update($validated);

        $this->sincronizarExpositores($evento, $expositores);

        return response()->json(['message' => 'Evento actualizado correctamente']);
    }

    public function destroy(string $evento)
    {
        $evento = Evento::findOrFail($evento);
        $evento->delete();

        return response()->json(['message' => 'Evento eliminado correctamente']);
    }

    public function cambiarEstado(Request $request, string $evento)
    {
        $evento = Evento::findOrFail($evento);

        $validated = $request->validate([
            'estado' => 'required|in:borrador,publicado,cerrado',
        ]);

        $evento->update($validated);

        return response()->json(['message' => 'Estado del evento actualizado correctamente']);
    }

    private function validarEvento(Request $request): array
    {
        if (is_string($request->input('expositores'))) {
            $request->merge(['expositores' => json_decode($request->input('expositores'), true) ?? []]);
        }

        // Los valores de hora pueden llegar con segundos (ej. "09:00:00") si provienen
        // sin modificar desde el registro guardado en BD; se normalizan a H:i.
        foreach (['hora_inicio', 'hora_fin'] as $campo) {
            if (is_string($request->input($campo)) && strlen($request->input($campo)) > 5) {
                $request->merge([$campo => substr($request->input($campo), 0, 5)]);
            }
        }

        return $request->validate([
            'titulo' => 'required|string|max:200',
            'tipo' => 'required|in:curso,seminario,capacitacion,taller,conferencia,otro',
            'descripcion' => 'nullable|string|max:10000',
            'modalidad' => 'required|in:presencial,virtual,hibrida',
            'lugar' => 'required_unless:modalidad,virtual|nullable|string|max:255',
            'enlace_virtual' => 'nullable|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'cupo_maximo' => 'nullable|integer|min:1',
            'horas_educativas' => 'nullable|integer|min:1',
            'color_primario' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'imagen_fondo' => 'nullable|image|max:4096',
            'estado' => 'nullable|in:borrador,publicado,cerrado',
            'expositores' => 'required|array|min:1',
            'expositores.*.nombre' => 'required|string|max:150',
            'expositores.*.entidad' => 'nullable|string|max:150',
        ]);
    }

    private function sincronizarExpositores(Evento $evento, array $expositores): void
    {
        $evento->expositores()->delete();

        foreach (array_values($expositores) as $orden => $expositor) {
            $evento->expositores()->create([
                'nombre' => $expositor['nombre'],
                'entidad' => $expositor['entidad'] ?? null,
                'orden' => $orden,
            ]);
        }
    }
}
