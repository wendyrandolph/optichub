<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceEmailMailable extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  // These public properties will automatically be made available to the Blade template
  public $clientName;
  public $invoice;
  public $items;
  public $total;

  /**
   * Create a new message instance.
   * The constructor takes the same parameters as your old helper method.
   */
  public function __construct(string $clientName, array $invoice, array $items, float $total)
  {
    $this->clientName = $clientName;
    $this->invoice = $invoice;
    $this->items = $items;
    $this->total = $total;
  }

  /**
   * Get the message envelope.
   * Defines the subject line and the sender.
   */
  public function envelope(): Envelope
  {
    // Subject line using the data passed into the constructor
    $subject = 'Invoice #' . ($this->invoice['invoice_number'] ?? 'Unknown');

    return new Envelope(
      subject: $subject,
      // You can optionally define the sender here, or rely on your .env configuration
      from: new \Illuminate\Mail\Mailables\Address(config('mail.from.address'), config('mail.from.name')),
    );
  }

  /**
   * Get the message content definition.
   * Points to the Blade template we created in the Canvas.
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.invoice-email',
      // Note: Data is automatically passed to the view via public properties
    );
  }

  /**
   * Get the attachments for the message.
   * This is where you would attach a PDF copy of the invoice if needed.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    // return [
    //     // Example: Attaching a file generated on the fly (requires a PDF generation service)
    //     // \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $pdfService->generate($this->invoice), 'Invoice_' . $this->invoice['invoice_number'] . '.pdf')
    // ];
    return [];
  }
}
