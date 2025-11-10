<?php
// app/Services/EmailSendService.php
namespace App\Services;

use App\Models\Email;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class EmailSendService
{
  public function sendAndLog(array $data): Email
  {
    // expected keys: tenant_id, subject, body, recipient_email, related_type?, related_id?
    $email = Email::create([
      'tenant_id' => $data['tenant_id'],
      'subject' => $data['subject'],
      'body' => $data['body'] ?? '',
      'recipient_email' => $data['recipient_email'],
      'related_type' => $data['related_type'] ?? null,
      'related_id' => $data['related_id'] ?? null,
    ]);

    try {
      Mail::send([], [], function (Message $message) use ($data) {
        $message->to($data['recipient_email'])
          ->subject($data['subject'])
          ->setBody($data['body'] ?? '', 'text/html');
      });

      $email->update(['date_sent' => now()]);
    } catch (\Throwable $e) {
      // Optional: capture error in future column
      throw $e;
    }

    return $email;
  }
}
