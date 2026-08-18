<?php

namespace App\Events;

use App\Models\EventoInscripcion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
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
     * Canales privados (requieren autorización en routes/channels.php): uno por
     * evento (para la pantalla de Inscritos de ese evento) y uno global (para el
     * contador de la tabla de eventos). No son públicos porque el payload incluye
     * datos personales (DNI, correo) de los inscritos.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('evento.' . $this->inscripcion->evento_id . '.inscripciones'),
            new PrivateChannel('inscripciones'),
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
