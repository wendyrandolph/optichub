<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public Client $client,
        public string $context = 'reminder'
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = $this->context === 'new' ? 'New Invoice' : 'Invoice Reminder';
        return new Envelope(
            subject: $prefix . ' #' . ($this->invoice->invoice_number ?? $this->invoice->id)
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-reminder'
        );
    }
}
