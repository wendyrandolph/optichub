<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Mail\GenericMessage;
use App\Services\TenantMailer;

class EmailComposeController extends Controller
{
  public function create(Tenant $tenant)
  {
    return view('emails.compose', ['tenant' => $tenant]);
  }

  public function store(Request $request, Tenant $tenant)
  {
    $data = $request->validate([
      'recipient_email' => ['required', 'email'],
      'subject'         => ['required', 'string', 'max:255'],
      'body'            => ['nullable', 'string'],
    ]);

    $mailer = TenantMailer::forTenant($tenant);

    // Optional custom From per-tenant setting
    $fromEmail = $tenant->mailSetting->from_email ?? null;
    $fromName  = $tenant->mailSetting->from_name ?? null;

    $mailable = new GenericMessage(
      subjectLine: $data['subject'],
      html: $data['body'] ?? null,
      text: strip_tags($data['body'] ?? '')
    );

    if ($fromEmail) {
      $mailable->from($fromEmail, $fromName ?: $fromEmail);
    }

    // Auto-BCC capture
    $mailer->withTenantAutoBcc($tenant)
      ->to($data['recipient_email'])
      ->send($mailable);

    return redirect()
      ->route('tenant.emails.index', ['tenant' => $tenant])
      ->with('success', 'Email sent and logged.');
  }
}
