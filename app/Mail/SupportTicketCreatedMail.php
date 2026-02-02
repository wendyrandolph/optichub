<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket, public string $pageUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Support Ticket: ' . ($this->ticket->subject ?? 'Ticket #' . $this->ticket->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-created',
            with: [
                'ticket' => $this->ticket,
                'pageUrl' => $this->pageUrl,
            ],
        );
    }
}
