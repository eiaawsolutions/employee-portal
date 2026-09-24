<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * "Find your workspace" reply — lists every active workspace the address
 * belongs to, each with a direct sign-in link. Only ever sent to the address
 * that asked, so it can't be used to learn about someone else's account.
 */
class FindWorkspaceMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, \App\Models\Tenant> $workspaces */
    public function __construct(public Collection $workspaces)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your EIAAW Workforce workspaces');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.find-workspace', with: ['workspaces' => $this->workspaces]);
    }
}
