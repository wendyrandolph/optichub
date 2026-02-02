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
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\MagicLink;
use App\Http\Middleware\RequireCapability;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Example custom binding
        $this->app->singleton(\App\Payments\ProviderFactory::class, fn() => new \App\Payments\ProviderFactory());
    }

    public function boot(): void
    {
        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('capability', RequireCapability::class);
        }

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
                        $channelsQuery = ChatChannel::query()
                            ->where('type', 'project')
                            ->whereIn('project_id', $projectIds);

                        if (\Illuminate\Support\Facades\Schema::hasColumn('chat_channels', 'tenant_id')) {
                            $channelsQuery->where('tenant_id', $clientUser->tenant_id);
                        }

                        $channelIds = $channelsQuery->pluck('id');

                        if ($channelIds->isNotEmpty()) {
                            $lastMessageAtSub = ChatMessage::select('created_at')
                                ->whereColumn('channel_id', 'c.id')
                                ->orderByDesc('created_at')
                                ->limit(1);
                            $lastMessageUserSub = ChatMessage::select('user_id')
                                ->whereColumn('channel_id', 'c.id')
                                ->orderByDesc('created_at')
                                ->limit(1);

                            $unreadCount = ChatChannel::withoutGlobalScope(\App\Scopes\TenantScope::class)
                                ->from('chat_channels as c')
                                ->whereIn('c.id', $channelIds)
                                ->whereNull('c.archived_at')
                                ->leftJoin('chat_reads as r', function ($join) use ($clientUser) {
                                    $join->on('r.channel_id', '=', 'c.id')
                                        ->where('r.user_id', '=', $clientUser->id);
                                })
                                ->when(
                                    \Illuminate\Support\Facades\Schema::hasColumn('chat_channels', 'tenant_id'),
                                    fn($q) => $q->where('c.tenant_id', $clientUser->tenant_id),
                                )
                                ->whereRaw('(' . $lastMessageAtSub->toSql() . ') is not null')
                                ->whereRaw('(' . $lastMessageAtSub->toSql() . ') > COALESCE(r.last_read_at, ?)', ['1970-01-01 00:00:00'])
                                ->whereRaw('(' . $lastMessageUserSub->toSql() . ') != ?', [$clientUser->id])
                                ->addBinding($lastMessageAtSub->getBindings())
                                ->addBinding($lastMessageAtSub->getBindings())
                                ->addBinding($lastMessageUserSub->getBindings())
                                ->count();
                        }
                    }
                }
            }

            $portalAlerts = collect();
            $portalAlertCount = 0;

            if ($clientUser && $clientUser->role === 'client' && $clientUser->contact_id) {
                $client = \App\Models\Client::where('tenant_id', $clientUser->tenant_id)
                    ->where('id', $clientUser->contact_id)
                    ->first();
                $lastSeen = $clientUser->portal_last_seen_at;

                if ($client) {
                    $clientId = (int) $client->id;

                    $taskBaseQuery = \App\Models\Task::query()
                        ->where('tenant_id', $clientUser->tenant_id)
                        ->where(function ($q) use ($clientId) {
                            $q->where('contact_id', $clientId)
                                ->orWhere(function ($sub) use ($clientId) {
                                    $sub->where('assign_type', 'client')
                                        ->where('assign_id', $clientId);
                                });
                        })
                        ->whereNotIn('status', ['completed', 'closed', 'archived']);

                    $overdueTasksQuery = (clone $taskBaseQuery)
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now()->toDateString());

                    $newTasksQuery = (clone $taskBaseQuery)
                        ->when($lastSeen, fn($q) => $q->where('created_at', '>', $lastSeen));

                    $overdueTasks = $overdueTasksQuery->orderBy('due_date')->limit(3)->get();
                    $newTasks = $newTasksQuery->orderByDesc('created_at')->limit(3)->get();

                    $overdueTaskCount = $overdueTasksQuery->count();
                    $newTaskCount = $newTasksQuery->count();

                    $invoiceBaseQuery = \App\Models\Invoice::query()
                        ->where('tenant_id', $clientUser->tenant_id)
                        ->where('contact_id', $clientId)
                        ->whereNotIn('status', ['paid', 'void', 'cancelled']);

                    $overdueInvoicesQuery = (clone $invoiceBaseQuery)
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', now()->toDateString());

                    $newInvoicesQuery = (clone $invoiceBaseQuery)
                        ->when($lastSeen, fn($q) => $q->where('created_at', '>', $lastSeen));

                    $overdueInvoices = $overdueInvoicesQuery->orderBy('due_date')->limit(3)->get();
                    $newInvoices = $newInvoicesQuery->orderByDesc('created_at')->limit(3)->get();

                    $overdueInvoiceCount = $overdueInvoicesQuery->count();
                    $newInvoiceCount = $newInvoicesQuery->count();

                    $portalAlertCount = $overdueTaskCount + $newTaskCount + $overdueInvoiceCount + $newInvoiceCount;

                    $portalAlerts = collect()
                        ->merge($overdueTasks->map(function ($task) {
                            $url = $task->project_id
                                ? route('portal.projects.show', $task->project_id)
                                : route('portal.tasks.comments.index', $task->id);
                            return [
                                'type' => 'task',
                                'title' => 'Task overdue: ' . ($task->title ?? 'Task'),
                                'meta' => $task->due_date ? 'Due ' . $task->due_date->format('M j, Y') : 'Past due',
                                'url' => $url,
                                'is_urgent' => true,
                                'created_at' => $task->due_date ?? $task->created_at,
                            ];
                        }))
                        ->merge($newTasks->map(function ($task) {
                            $url = $task->project_id
                                ? route('portal.projects.show', $task->project_id)
                                : route('portal.tasks.comments.index', $task->id);
                            return [
                                'type' => 'task',
                                'title' => 'New task assigned: ' . ($task->title ?? 'Task'),
                                'meta' => $task->due_date ? 'Due ' . $task->due_date->format('M j, Y') : 'No due date',
                                'url' => $url,
                                'is_urgent' => false,
                                'created_at' => $task->created_at,
                            ];
                        }))
                        ->merge($overdueInvoices->map(function ($invoice) {
                            return [
                                'type' => 'invoice',
                                'title' => 'Invoice overdue: ' . ($invoice->invoice_number ?? 'Invoice'),
                                'meta' => $invoice->due_date ? 'Due ' . $invoice->due_date->format('M j, Y') : 'Past due',
                                'url' => route('portal.invoices.show', $invoice->id),
                                'is_urgent' => true,
                                'created_at' => $invoice->due_date ?? $invoice->created_at,
                            ];
                        }))
                        ->merge($newInvoices->map(function ($invoice) {
                            return [
                                'type' => 'invoice',
                                'title' => 'New invoice: ' . ($invoice->invoice_number ?? 'Invoice'),
                                'meta' => $invoice->due_date ? 'Due ' . $invoice->due_date->format('M j, Y') : 'No due date',
                                'url' => route('portal.invoices.show', $invoice->id),
                                'is_urgent' => false,
                                'created_at' => $invoice->created_at,
                            ];
                        }))
                        ->sortByDesc('is_urgent')
                        ->sortByDesc('created_at')
                        ->values()
                        ->take(6);
                }
            }

            $view->with('portalTheme', $portalTheme);
            $view->with('portalUnreadCount', $unreadCount);
            $view->with('portalAlerts', $portalAlerts);
            $view->with('portalAlertCount', $portalAlertCount);
            $view->with('portalMagicLink', $portalMagicLink);
            $view->with('portalMagicLinkDaysLeft', $portalMagicLinkDaysLeft);
        });
    }
}
