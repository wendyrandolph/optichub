<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailer; // <-- register macro on the real Mailer
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Support\Branding;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectConversation;
use App\Models\ProjectMessage;
use App\Models\MagicLink;

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

        // Pagination (use Laravel's Tailwind defaults)
        Paginator::useTailwind();

        View::composer(['portal.*', 'layouts.portal', 'partials.client.*'], function ($view) {
            $clientUser = auth('client')->user();
            $tenant = $clientUser?->tenant;
            $portalTheme = Branding::portalTheme($tenant);

            $unreadCount = 0;
            $portalMagicLink = null;
            $portalMagicLinkDaysLeft = null;
            if ($clientUser && $clientUser->contact_id) {
                $client = Client::where('tenant_id', $clientUser->tenant_id)
                    ->where('id', $clientUser->contact_id)
                    ->first();

                if ($client) {
                    $portalMagicLink = MagicLink::where('user_id', $clientUser->id)
                        ->orderByDesc('expires_at')
                        ->first();
                    if ($portalMagicLink?->expires_at) {
                        $portalMagicLinkDaysLeft = now()->diffInDays($portalMagicLink->expires_at, false);
                    }

                    $companyId = $client->client_company_id;
                    $projectIds = Project::query()
                        ->where('tenant_id', $client->tenant_id)
                        ->where(function ($q) use ($client, $companyId) {
                            $q->where('contact_id', $client->id);
                            if ($companyId) {
                                $q->orWhereIn('contact_id', function ($sub) use ($client) {
                                    $sub->select('id')
                                        ->from('contacts')
                                        ->where('tenant_id', $client->tenant_id)
                                        ->where('client_company_id', $client->client_company_id);
                                });
                            }
                        })
                        ->pluck('id');

                    if ($projectIds->isNotEmpty()) {
                        $conversationIds = ProjectConversation::whereIn('project_id', $projectIds)
                            ->pluck('id');

                        if ($conversationIds->isNotEmpty()) {
                            $lastMessageSub = ProjectMessage::select('sender_type')
                                ->whereColumn('conversation_id', 'pc.id')
                                ->orderByDesc('created_at')
                                ->limit(1);

                            $unreadCount = ProjectConversation::from('project_conversations as pc')
                                ->whereIn('pc.id', $conversationIds)
                                ->whereNotNull('pc.last_message_at')
                                ->whereRaw('pc.last_message_at > COALESCE(pc.public_last_viewed_at, ?)', ['1970-01-01 00:00:00'])
                                ->whereRaw('(' . $lastMessageSub->toSql() . ') = ?', ['tenant'])
                                ->addBinding($lastMessageSub->getBindings())
                                ->count();
                        }
                    }
                }
            }

            $view->with('portalTheme', $portalTheme);
            $view->with('portalUnreadCount', $unreadCount);
            $view->with('portalMagicLink', $portalMagicLink);
            $view->with('portalMagicLinkDaysLeft', $portalMagicLinkDaysLeft);
        });
    }
}
