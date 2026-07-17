<?php

namespace App\Mail;

use App\Models\EventoInscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscripcionConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EventoInscripcion $inscripcion)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de inscripción: ' . $this->inscripcion->evento->titulo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscripcion_confirmada',
            with: [
                'evento' => $this->inscripcion->evento,
                'inscripcion' => $this->inscripcion,
            ],
        );
    }
}
