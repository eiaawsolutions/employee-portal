<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WeeklyPendingSweepMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $type,          // consent | aarf_employee | aarf_it | leave | claims_manager | claims_hr
        public Collection $items,
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'consent'        => 'Reminder: Pending Profile Acknowledgement',
            'aarf_employee'  => 'Reminder: Pending Asset Form Acknowledgement',
            'aarf_it'        => 'Reminder: AARF Forms Awaiting IT Acknowledgement',
            'leave'          => "Reminder: {$this->items->count()} Pending Leave Request(s)",
            'claims_manager' => "Reminder: {$this->items->count()} Expense Claim(s) Awaiting Your Approval",
            'claims_hr'      => "Reminder: {$this->items->count()} Expense Claim(s) Awaiting HR Approval",
        ];

        return new Envelope(
            subject: $subjects[$this->type] ?? 'Reminder: Pending Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-pending-sweep',
            with: [
                'recipientName' => $this->recipientName,
                'type'          => $this->type,
                'items'         => $this->items,
            ],
        );
    }
}
