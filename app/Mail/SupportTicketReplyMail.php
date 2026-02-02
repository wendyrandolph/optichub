<?php

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket, public string $replyBody)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Support Reply: ' . ($this->ticket->subject ?? 'Ticket #' . $this->ticket->id),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-reply',
            with: [
                'ticket' => $this->ticket,
                'replyBody' => $this->replyBody,
            ],
        );
    }
}
