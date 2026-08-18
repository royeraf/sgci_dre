<?php

namespace App\Events;

use App\Models\EventoInscripcion;
use App\Models\EventoInscripcionAsistencia;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AsistenciaAutomarcada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EventoInscripcion $inscripcion;
    public EventoInscripcionAsistencia $asistencia;

    public function __construct(EventoInscripcion $inscripcion, EventoInscripcionAsistencia $asistencia)
    {
        $this->inscripcion = $inscripcion;
        $this->asistencia = $asistencia;
    }

    /**
     * Mismo canal privado que ya usa NewInscripcionCreated para la pantalla de
     * Inscritos de este evento (autorización ya definida en routes/channels.php).
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('evento.' . $this->inscripcion->evento_id . '.inscripciones'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'asistencia-automarcada';
    }

    public function broadcastWith(): array
    {
        return [
            'inscripcion_id' => $this->inscripcion->id,
            'asistencia' => [
                'fecha' => $this->asistencia->fecha->format('Y-m-d'),
                'marcado_en' => $this->asistencia->marcado_en?->format('Y-m-d H:i'),
                'auto' => true,
            ],
        ];
    }
}
