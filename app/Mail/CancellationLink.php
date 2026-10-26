<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CancellationLink extends Mailable
{
    public function __construct(public string $confirmationUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bevestig je afmelding bij t Fratertje');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.cancellation');
    }
}
