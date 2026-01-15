@extends('layouts.app')

@section('title', 'Mailbox Sync')

@section('content')
    @php
        $account = $account ?? null;
        $status = $account?->status ?? 'disconnected';
        $statusPill = match ($status) {
            'connected' => 'oh-pill oh-pill--success',
            'error' => 'oh-pill oh-pill--danger',
            'revoked' => 'oh-pill oh-pill--warning',
            default => 'oh-pill',
        };
        $showActions = ($gmailConfigured ?? false) && ($gmailEnabled ?? false);
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
                <h1 class="text-2xl font-semibold text-text-base">Mailbox Sync</h1>
                <p class="text-sm text-text-subtle">Connect your Gmail to log emails and sync metadata into Renlo.</p>
                @if (!$showActions)
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 inline-block mt-2">
                        Gmail not configured or disabled. Set ENABLE_GMAIL_SYNC=true and add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and redirect URI in .env to enable.
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($showActions && $account)
                    <form method="POST" action="{{ route('tenant.settings.mailbox.sync', ['tenant' => $tenantId]) }}">
                        @csrf
                        <button type="submit" class="oh-btn oh-btn--secondary">Sync now</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="oh-card border border-border-default/60 rounded-xl p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-text-base">Your Gmail</h2>
                        <p class="text-sm text-text-subtle">Each team member connects their own mailbox.</p>
                    </div>
                    @if ($account)
                        <span class="{{ $statusPill }}">{{ ucfirst($status) }}</span>
                    @else
                        <span class="oh-pill">Not connected</span>
                    @endif
                </div>

                <div class="rounded-lg border border-border-default/60 bg-surface-card p-4 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-text-base">
                                {{ $account?->email_address ?? auth()->user()?->email ?? '—' }}
                            </p>
                            <p class="text-xs text-text-subtle">
                                Status: {{ ucfirst($status) }} |
                                Last sync:
                                {{ $account?->last_synced_at ? $account->last_synced_at->diffForHumans() : '—' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if ($showActions)
                                @if ($account)
                                    <form method="POST"
                                        action="{{ route('tenant.settings.mailbox.disconnect', ['tenant' => $tenantId]) }}">
                                        @csrf
                                        <button type="submit" class="oh-btn">Disconnect</button>
                                    </form>
                                @else
                                    <a href="{{ route('tenant.settings.mailbox.connect', ['tenant' => $tenantId]) }}"
                                        class="oh-btn oh-btn--primary">
                                        Connect Gmail
                                    </a>
                                @endif
                            @else
                                <button type="button" class="oh-btn cursor-not-allowed opacity-70" disabled>
                                    Connect Gmail
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($account?->last_error)
                        <div class="text-xs text-status-danger bg-status-danger/10 border border-status-danger/40 rounded-lg p-2">
                            {{ $account->last_error }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="oh-card border border-border-default/60 rounded-xl p-5 space-y-3">
                <h3 class="text-base font-semibold text-text-base">Logging rules</h3>
                <p class="text-sm text-text-subtle">
                    Default: only log emails that match existing contact emails. Unmatched emails are ignored unless you enable
                    review mode later.
                </p>
                <ul class="list-disc list-inside text-sm text-text-subtle space-y-1">
                    <li>Log only matched contacts: {{ $settings->log_only_matched_contacts ? 'On' : 'Off' }}</li>
                    <li>Unmatched behavior: {{ ucfirst($settings->unmatched_behavior) }}</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
