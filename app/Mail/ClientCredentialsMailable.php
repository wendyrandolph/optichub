<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class ClientCredentialsMailable extends Mailable
{
    use Queueable, SerializesModels;

    public Client $contact;
    public string $tempPassword;

    /**
     * @param Client $contact Contact/Client receiving the login
     * @param string  $tempPassword Temporary password to send
     */
    public function __construct(Client $contact, string $tempPassword)
    {
        $this->contact = $contact;
        $this->tempPassword = $tempPassword;
    }

    public function envelope(): Envelope
    {
        $name = trim(($this->contact->firstName ?? '') . ' ' . ($this->contact->lastName ?? '')) ?: null;
        return new Envelope(
            to: [new Address($this->contact->email, $name)],
            subject: 'Your client portal login'
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client-credentials',
            with: [
                'email' => $this->contact->email,
                'tempPassword' => $this->tempPassword,
            ],
        );
    }
}
