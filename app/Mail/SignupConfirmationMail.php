<?php

namespace App\Mail;

use App\Models\SignupInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignupConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SignupInvite $invite)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your EIAAW Workforce signup',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.signup-confirmation',
            with: [
                'invite' => $this->invite,
                'confirmUrl' => url('/signup/confirm/' . $this->invite->confirmation_token),
            ],
        );
    }
}
