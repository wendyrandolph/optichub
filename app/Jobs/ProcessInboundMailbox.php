<?php
// app/Jobs/ProcessInboundMailbox.php
namespace App\Jobs;

use Webklex\IMAP\Facades\Client;
use App\Models\Email;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class ProcessInboundMailbox extends Job
{
  public function handle(): void
  {
    $client = Client::account('default'); // configured inbound@ account
    $client->connect();

    $folder = $client->getFolder('INBOX');
    $messages = $folder->messages()->unseen()->get();

    foreach ($messages as $message) {
      $toAddresses = collect($message->getTo())->pluck('mail')->all();
      $capture = collect($toAddresses)->first(fn($addr) => str_contains($addr, '+'));
      if (!$capture) {
        $message->setFlag('Seen');
        continue;
      }

      if (!preg_match('/\+(\d+)-([a-z0-9]+)@/i', $capture, $m)) {
        $message->setFlag('Seen');
        continue;
      }
      [$full, $tenantId, $token] = $m;
      $tenant = Tenant::find($tenantId);
      if (!$tenant || $tenant->mailSetting?->inbound_token !== $token) {
        $message->setFlag('Seen');
        continue;
      }

      $html = $message->getHTMLBody(true) ?: null;
      $text = $message->getTextBody() ?: null;
      $mid  = $message->getMessageId();

      // Attachments
      $files = [];
      foreach ($message->getAttachments() as $att) {
        $path = Storage::disk('local')->put(
          "tenants/{$tenant->id}/emails/attachments",
          $att->content
        );
        $files[] = ['name' => $att->name, 'path' => $path, 'size' => strlen($att->content)];
      }

      Email::firstOrCreate(
        ['tenant_id' => $tenant->id, 'message_id' => $mid ?? bin2hex(random_bytes(8))],
        [
          'direction' => 'inbound',
          'subject' => $message->getSubject(),
          'from_email' => optional($message->getFrom())->first()?->mail,
          'to' => $toAddresses,
          'cc' => collect($message->getCc())->pluck('mail')->all(),
          'bcc' => collect($message->getBcc())->pluck('mail')->all(),
          'body_html' => $html,
          'body_text' => $text,
          'received_at' => $message->getDate(),
          'attachments' => $files,
          'headers' => [], // optional
        ]
      );

      $message->setFlag('Seen');
    }
    \App\Services\EmailAssociation::attachToLeadOrClient($emailModel, $tenant->id);
  }
}
