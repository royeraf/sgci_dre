<?php

namespace App\Events;

use App\Models\EventoInscripcion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewInscripcionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EventoInscripcion $inscripcion;

    public function __construct(EventoInscripcion $inscripcion)
    {
        $this->inscripcion = $inscripcion;
    }

    /**
     * Se transmite en un canal por evento (para la pantalla de Inscritos de ese
     * evento) y en un canal global (para el contador de la tabla de eventos).
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('evento.' . $this->inscripcion->evento_id . '.inscripciones'),
            new Channel('inscripciones'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new-inscripcion';
    }

    public function broadcastWith(): array
    {
        return $this->inscripcion->toAdminArray();
    }
}
