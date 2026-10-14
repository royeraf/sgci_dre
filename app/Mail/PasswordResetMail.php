<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $password,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva contraseña de acceso - SGCI DRE Huánuco',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password_reset',
            with: [
                'nombre'   => $this->nombre,
                'password' => $this->password,
            ],
        );
    }
}
