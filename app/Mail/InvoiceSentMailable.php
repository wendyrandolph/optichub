<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class InvoiceSentMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        $tenant = $this->invoice->tenant;
        $replyTo = $tenant?->reply_to_email ?: $this->fallbackReplyTo($tenant);
        $fromName = $tenant?->brand_name ?: $tenant?->name ?: config('mail.from.name');

        return new Envelope(
            subject: 'Invoice #' . ($this->invoice->invoice_number ?? $this->invoice->id),
            from: new Address(config('mail.from.address'), $fromName),
            replyTo: $replyTo ? [new Address($replyTo, $tenant?->brand_name ?: $tenant?->name)] : [],
        );
    }

    public function content(): Content
    {
        $tenant = $this->invoice->tenant;
        $logoUrl = $tenant?->logo_path ? Storage::url($tenant->logo_path) : null;

        return new Content(
            markdown: 'emails.invoice-sent',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'brandName' => $tenant?->brand_name ?: $tenant?->name,
                'logoUrl' => $logoUrl,
                'primaryColor' => $tenant?->primary_color,
            ],
        );
    }

    private function fallbackReplyTo(?\App\Models\Tenant $tenant): ?string
    {
        if (!$tenant) {
            return null;
        }

        return User::where('tenant_id', $tenant->id)
            ->whereIn('role', ['admin', 'owner', 'super_admin', 'superadmin'])
            ->orderBy('id')
            ->value('email');
    }
}
