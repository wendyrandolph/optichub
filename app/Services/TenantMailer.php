<?php

namespace App\Services;

use App\Models\Tenant;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Illuminate\Support\Facades\Mail;

class TenantMailer
{
  public static function forTenant(Tenant $tenant): \Illuminate\Mail\Mailer
  {
    $ms = $tenant->mailSetting;
    if (!$ms || ($ms->provider === 'smtp' && !$ms->smtp_host)) {
      // Default Laravel mailer
      return Mail::mailer();
    }

    if ($ms->provider === 'smtp') {
      $transport = new EsmtpTransport(
        $ms->smtp_host,
        $ms->smtp_port ?: 587,
        (bool) $ms->smtp_tls
      );

      if ($ms->smtp_user && $ms->smtp_password) {
        $transport->setUsername($ms->smtp_user);
        $transport->setPassword($ms->smtp_password);
      }

      $symfonyMailer = new \Symfony\Component\Mailer\Mailer($transport);

      // View factory from container
      $view = app('view');

      // Create a named mailer instance using the Symfony mailer
      return new \Illuminate\Mail\Mailer('tenant-smtp', Mail::getSwiftMailer() /*legacy*/, $view, $symfonyMailer);
    }

    // TODO: add Mailgun/Postmark/SendGrid branches later.
    return Mail::mailer();
  }
}
