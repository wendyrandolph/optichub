<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use App\Models\Email;

class LogSentEmail
{
  public function handle(MessageSent $event): void
  {
    $msg  = $event->message;           // Symfony Email
    $hdrs = $msg->getHeaders();
    $mId  = (string) ($hdrs->get('Message-ID')?->getBody() ?? '');
    $subj = $msg->getSubject();

    $from = array_key_first($msg->getFrom() ?? []) ?: null;
    $to   = array_keys($msg->getTo() ?? []);
    $cc   = array_keys($msg->getCc() ?? []);
    $bcc  = array_keys($msg->getBcc() ?? []);

    // Resolve tenant (from container binding or auth fallback)
    $tenant = app()->bound('currentTenant') ? app('currentTenant') : (auth()->user()->tenant ?? null);
    if (!$tenant) return;

    Email::firstOrCreate(
      ['tenant_id' => $tenant->id, 'message_id' => $mId ?: bin2hex(random_bytes(8))],
      [
        'direction'  => 'outbound',
        'subject'    => $subj,
        'from_email' => $from,
        'to'         => $to,
        'cc'         => $cc,
        'bcc'        => $bcc,
        'body_html'  => method_exists($msg, 'getHtmlBody') ? $msg->getHtmlBody() : null,
        'body_text'  => method_exists($msg, 'getTextBody') ? $msg->getTextBody() : null,
        'headers'    => [],
        'sent_at'    => now(),
      ]
    );
  }
}
