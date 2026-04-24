<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DedicatedDatabaseRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $requester,
        public array $data,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[EIAAW Workforce] Dedicated-DB request · {$this->tenant->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dedicated-database-request',
            with: [
                'tenant' => $this->tenant,
                'requester' => $this->requester,
                'data' => $this->data,
            ],
        );
    }
}
