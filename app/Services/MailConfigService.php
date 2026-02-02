<?php

namespace App\Services;

use App\Models\ProviderEmailSetting;
use Illuminate\Support\Facades\Schema;

class MailConfigService
{
    public function applyProviderSettings(): void
    {
        if (!Schema::hasTable('provider_email_settings')) {
            return;
        }

        $settings = ProviderEmailSetting::query()->latest()->first();
        if (!$settings) {
            return;
        }

        $config = $settings->toMailConfig();

        if (!empty($config['mailer'])) {
            config(['mail.default' => $config['mailer']]);
        }

        $mailer = $config['mailer'] ?? 'smtp';
        if ($mailer === 'smtp') {
            config([
                'mail.mailers.smtp.host' => $config['host'] ?? config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => $config['port'] ?? config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username' => $config['username'] ?? config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password' => $config['password'] ?? config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => $config['encryption'] ?? config('mail.mailers.smtp.encryption'),
            ]);
        }

        if (!empty($config['from']['address'])) {
            config([
                'mail.from.address' => $config['from']['address'],
                'mail.from.name' => $config['from']['name'] ?? config('mail.from.name'),
            ]);
        }
    }
}
