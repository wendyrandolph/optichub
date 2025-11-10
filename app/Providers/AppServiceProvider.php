<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailer; // <-- register macro on the real Mailer

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Example custom binding
        $this->app->singleton(\App\Payments\ProviderFactory::class, fn() => new \App\Payments\ProviderFactory());
    }

    public function boot(): void
    {
        /**
         * URL tenant defaults (safe in web only).
         * Ensures route('tenant.*') doesn’t need you to pass ['tenant' => ...] every time.
         */
        if (! $this->app->runningInConsole()) {
            try {
                $t = request()->route('tenant') ?? null;

                // Accept route-model or scalar id
                if ($t instanceof Tenant) {
                    URL::defaults(['tenant' => $t->getKey()]);
                } elseif (is_numeric($t)) {
                    URL::defaults(['tenant' => (int) $t]);
                } elseif (function_exists('tenant') && tenant()) {
                    URL::defaults(['tenant' => tenant()->getTenantKey()]);
                }
            } catch (\Throwable $e) {
                // No active request (jobs, some tests) — ignore
            }
        }

        /**
         * Mail: only run when the mail manager is available.
         * (Prevents "Target class [mail.manager] does not exist" during early boot/CLI edge cases.)
         */
        if ($this->app->bound('mail.manager')) {

            // Per-tenant default From (optional)
            $tenant = $this->app->bound('currentTenant') ? app('currentTenant') : null;
            $fromEmail = $tenant->mail_from_address ?? config('mail.from.address');
            $fromName  = $tenant->mail_from_name ?? config('mail.from.name');

            if ($fromEmail) {
                Mail::alwaysFrom($fromEmail, $fromName);
            }

            /**
             * Add a macro to the *Mailer* so you can call:
             *   Mail::to(...)->withTenantAutoBcc($tenant)->send(new ...)
             */
            if (! Mailer::hasMacro('withTenantAutoBcc')) {
                Mailer::macro('withTenantAutoBcc', function (Tenant $tenant) {
                    /** @var \Illuminate\Mail\Mailer $this */
                    $ms = method_exists($tenant, 'mailSetting') ? $tenant->mailSetting : null;

                    if (
                        ! $ms?->auto_bcc_outbound ||
                        ! $ms?->inbound_domain ||
                        ! $ms?->inbound_localpart ||
                        ! $ms?->inbound_token
                    ) {
                        return $this; // no-op if not configured
                    }

                    $capture = sprintf(
                        '%s+%d-%s@%s',
                        $ms->inbound_localpart,
                        $tenant->getKey(),
                        $ms->inbound_token,
                        $ms->inbound_domain
                    );

                    return $this->bcc($capture);
                });
            }
        }
    }
}
