<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class MarketingContactMailable extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  /**
   * The submitted data from the contact form.
   */
  public string $contactName;
  public string $contactEmail;
  public string $contactTopic;
  public string $contactMessage;

  /**
   * Create a new message instance.
   */
  public function __construct(string $name, string $email, string $topic, string $message)
  {
    $this->contactName = $name;
    $this->contactEmail = $email;
    $this->contactTopic = $topic;
    $this->contactMessage = $message;

    // Use Markdown for better formatting in the email body
    $this->markdown = 'emails.marketing.contact-submission';
  }

  /**
   * Get the message envelope.
   * Replaces procedural setting of the subject and recipient.
   */
  public function envelope(): Envelope
  {
    // Set the recipient (your team's email) and subject
    return new Envelope(
      // Use your primary support/sales email as the TO address
      to: new Address(config('mail.marketing_recipient.address', 'info@yourdomain.com'), 'Optic Hub Team'),

      // Set the subject line dynamically
      subject: "New Contact Form Submission: {$this->contactTopic}",

      // Set the reply-to address to the client's email so you can hit 'Reply'
      replyTo: [
        new Address($this->contactEmail, $this->contactName),
      ],
    );
  }

  /**
   * Get the message content definition.
   * Defines the Blade file used for the email body.
   */
  public function content(): Content
  {
    return new Content(
      markdown: 'emails.marketing.contact-submission',
      // Pass the public properties to the Blade view
      with: [
        'name'    => $this->contactName,
        'email'   => $this->contactEmail,
        'topic'   => $this->contactTopic,
        'message' => $this->contactMessage,
      ],
    );
  }

  /**
   * Build method is not strictly necessary when using envelope() and content(), 
   * but can be used for backward compatibility or complex attachments.
   * We implement ShouldQueue to ensure the email is sent in the background.
   */
}
