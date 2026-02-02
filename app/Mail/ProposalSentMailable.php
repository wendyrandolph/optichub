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
use Illuminate\Support\Facades\Storage;
use App\Models\User;
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
    $tenant = $this->proposal->tenant;
    $replyTo = $tenant?->reply_to_email ?: $this->fallbackReplyTo($tenant);
    $fromName = $tenant?->brand_name ?: $tenant?->name ?: config('mail.from.name');

    return new Envelope(
      subject: 'New Proposal: ' . $this->proposal->title,
      from: new Address(config('mail.from.address'), $fromName),
      replyTo: $replyTo ? [new Address($replyTo, $tenant?->brand_name ?: $tenant?->name)] : [],
    );
  }
  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    // Pass the proposal object and the public URL to the Blade view
    $proposalUrl = route('proposals.client.show', $this->proposal->unique_share_token);
    $tenant = $this->proposal->tenant;
    $logoUrl = $tenant?->logo_path ? Storage::url($tenant->logo_path) : null;

    return new Content(
      markdown: 'emails.proposals.sent',
      with: [
        'proposal' => $this->proposal,
        'proposalUrl' => $proposalUrl,
        'clientFirstName' => $this->proposal->client->firstName ?? $this->proposal->lead?->name ?? 'Client',
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
