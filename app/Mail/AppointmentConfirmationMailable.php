<?php:Appointment Confirmation Mailable:app/Mail/AppointmentConfirmationMailable.php
namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmationMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public string $googleLink;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, string $googleLink)
    {
        $this->appointment = $appointment;
        $this->googleLink = $googleLink;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $adminEmail = config('mail.admin_notifications.address', 'appointments@causeywebsolutions.com');

        return new Envelope(
            to: [new Address($this->appointment->client_email, $this->appointment->client_name)],
            cc: [$adminEmail], // Send copy to yourself (the company)
            subject: 'Appointment Confirmation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.scheduler.confirmation',
            with: [
                'appointment' => $this->appointment,
                'googleLink' => $this->googleLink,
            ],
        );
    }
}