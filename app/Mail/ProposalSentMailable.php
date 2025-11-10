<?php

//:Proposal Sent Mailable:app/Mail/ProposalSentMailable.php
namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class ProposalSentMailable extends Mailable implements ShouldQueue
{
  use Queueable, SerializesModels;

  /**
   * The Proposal instance.
   */
  public Proposal $proposal;

  /**
   * Create a new message instance.
   */
  public function __construct(Proposal $proposal)
  {
    $this->proposal = $proposal;
  }

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    $client = $this->proposal->client;
    $proposalUrl = route('proposals.client.show', $this->proposal->unique_share_token);

    return new Envelope(
      to: new Address($client->email, $client->name),
      subject: 'New Proposal: ' . $this->proposal->title,

      // Set the sender (can be configured in .env or config/mail.php)
      from: new Address(config('mail.from.address'), config('mail.from.name')),
    );
  }
  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    // Pass the proposal object and the public URL to the Blade view
    $proposalUrl = route('proposals.client.show', $this->proposal->unique_share_token);

    return new Content(
      markdown: 'emails.proposals.sent',
      with: [
        'proposal' => $this->proposal,
        'proposalUrl' => $proposalUrl,
        'clientFirstName' => $this->proposal->client->firstName ?? 'Client',
      ],
    );
  }
}
