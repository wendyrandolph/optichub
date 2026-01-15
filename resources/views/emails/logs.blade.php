@extends('layouts.app')

@section('title', 'Email Logs')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Emails</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Email Logs</h1>
                    <p class="text-sm text-text-subtle mt-1">View synced Gmail metadata. Mine by default; team view for admins.</p>
                </div>
            </div>
        </div>

        <div class="oh-card border border-border-default/60 rounded-xl p-4 md:p-5 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-text-base">Sync status</p>
                    @if (!$gmailConfigured)
                        <p class="text-xs text-text-subtle">Gmail not configured. Enable in Settings → Mailbox Sync.</p>
                    @elseif (!$currentAccount)
                        <p class="text-xs text-text-subtle">No mailbox connected for your user.</p>
                    @else
                        <p class="text-xs text-text-subtle">
                            {{ $currentAccount->email_address ?? 'Mailbox' }} • Status: {{ ucfirst($currentAccount->status ?? 'disconnected') }}
                        </p>
                        <p class="text-xs text-text-subtle">
                            Last sync: {{ $currentAccount->last_sync_finished_at?->diffForHumans() ?? ($currentAccount->last_synced_at?->diffForHumans() ?? '—') }}
                        </p>
                        @if ($currentAccount->last_sync_stats)
                            <p class="text-xs text-text-subtle">
                                Stats: processed {{ $currentAccount->last_sync_stats['processed'] ?? 0 }},
                                inserted {{ $currentAccount->last_sync_stats['inserted'] ?? 0 }},
                                updated {{ $currentAccount->last_sync_stats['updated'] ?? 0 }},
                                skipped {{ $currentAccount->last_sync_stats['skipped_unmatched'] ?? 0 }},
                                errors {{ $currentAccount->last_sync_stats['errors'] ?? 0 }}.
                            </p>
                        @endif
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($gmailConfigured && $currentAccount)
                        <form method="POST" action="{{ route('tenant.settings.mailbox.sync', ['tenant' => $tenantId]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--secondary"
                                @disabled($currentAccount->sync_in_progress)>
                                {{ $currentAccount->sync_in_progress ? 'Sync in progress' : 'Sync now' }}
                            </button>
                        </form>
                    @else
                        <button type="button" class="oh-btn cursor-not-allowed opacity-70" disabled>Sync now</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="oh-card border border-border-default/60 rounded-xl p-4 md:p-5 space-y-4">
            <form method="GET" class="flex flex-col gap-3">
                <div class="grid gap-3 md:grid-cols-4 items-end">
                    <div class="md:col-span-2">
                        <label class="text-xs text-text-subtle">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="oh-input w-full h-10" placeholder="Subject, from, to...">
                    </div>
                    <div>
                        <label class="text-xs text-text-subtle">Direction</label>
                        <select name="direction" class="oh-select w-full h-10">
                            <option value="">All</option>
                            <option value="inbound" @selected(request('direction')==='inbound')>Inbound</option>
                            <option value="outbound" @selected(request('direction')==='outbound')>Outbound</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-text-subtle">Status</label>
                        <select name="status" class="oh-select w-full h-10">
                            <option value="">All</option>
                            <option value="logged" @selected(request('status')==='logged')>Logged</option>
                            <option value="needs_review" @selected(request('status')==='needs_review')>Needs review</option>
                            <option value="ignored" @selected(request('status')==='ignored')>Ignored</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($isTenantAdmin)
                        <div class="inline-flex items-center gap-2">
                            <span class="text-xs text-text-subtle">Scope</span>
                            <a href="{{ request()->fullUrlWithQuery(['scope' => 'mine']) }}"
                                class="oh-chip {{ $viewMode === 'mine' ? 'oh-chip--active' : '' }}">Mine</a>
                            <a href="{{ request()->fullUrlWithQuery(['scope' => 'team']) }}"
                                class="oh-chip {{ $viewMode === 'team' ? 'oh-chip--active' : '' }}">Team</a>
                        </div>
                    @else
                        <span class="text-xs text-text-subtle">Scope: Mine</span>
                    @endif
                    @if ($viewMode === 'team' && $isTenantAdmin)
                        <select name="member" class="oh-select h-10">
                            <option value="">All members</option>
                            @foreach ($accounts as $acct)
                                <option value="{{ $acct->user_id }}" @selected(request('member')==$acct->user_id)>
                                    {{ $acct->email_address ?? 'User #'.$acct->user_id }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <label class="inline-flex items-center gap-2 text-xs text-text-subtle">
                        <input type="checkbox" name="needs_review" value="1" class="rounded border-border-default"
                            @checked(request('needs_review'))>
                        Needs review
                    </label>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                    <a href="{{ route('tenant.email-logs.index', ['tenant' => $tenantId]) }}" class="oh-btn">Reset</a>
                </div>
            </form>
        </div>

        <div class="oh-card border border-border-default/60 rounded-xl p-4">
            @if (!($gmailConfigured ?? false))
                <div class="text-sm text-text-subtle py-6 text-center">
                    Gmail sync is not configured. Enable it in Settings → Mailbox Sync.
                </div>
            @endif

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-text-subtle bg-surface-muted/60">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Subject</th>
                            <th class="px-4 py-2 text-left font-medium">Participants</th>
                            <th class="px-4 py-2 text-left font-medium">Direction</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Actions</th>
                            <th class="px-4 py-2 text-left font-medium">Sent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-surface-accent/40 transition">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-text-base truncate">{{ $log->subject ?? '(No subject)' }}</div>
                                    <div class="text-xs text-text-subtle truncate">{{ $log->snippet }}</div>
                                </td>
                                <td class="px-4 py-3 text-text-subtle">
                                    <div class="truncate">From: {{ $log->from_email }}</div>
                                    <div class="truncate">To: {{ implode(', ', $log->to_emails ?? []) }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="oh-pill">{{ ucfirst($log->direction ?? '—') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="oh-pill">{{ str_replace('_',' ', $log->status) }}</span>
                                    @if ($log->needs_review)
                                        <span class="oh-pill oh-pill--warning ml-1">Needs review</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 space-y-2">
                                    @if ($log->needs_review && $isTenantAdmin)
                                        <form method="POST"
                                            action="{{ route('tenant.email-logs.link-contact', ['tenant' => $tenantId, 'log' => $log->id]) }}"
                                            class="flex items-center gap-2">
                                            @csrf
                                            <select name="contact_id" class="oh-select h-9">
                                                <option value="">Link contact…</option>
                                                @foreach ($contactOptions as $contact)
                                                    <option value="{{ $contact->id }}">
                                                        {{ $contact->firstName }} {{ $contact->lastName }} ({{ $contact->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="oh-btn oh-btn--primary h-9 text-xs px-3">Link</button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('tenant.email-logs.ignore', ['tenant' => $tenantId, 'log' => $log->id]) }}">
                                            @csrf
                                            <button type="submit" class="oh-btn h-9 text-xs">Ignore</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-text-subtle">
                                    {{ optional($log->sent_at)->diffForHumans() ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-text-subtle">
                                    No email logs yet. Connect Gmail in Settings → Mailbox Sync.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="md:hidden grid gap-3">
                @forelse ($logs as $log)
                    <div class="oh-card border border-border-default/60 rounded-lg p-3 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-semibold text-text-base truncate">{{ $log->subject ?? '(No subject)' }}</div>
                            <span class="oh-pill text-xs">{{ ucfirst($log->direction ?? '—') }}</span>
                        </div>
                            <div class="text-xs text-text-subtle space-y-1">
                                <div>From: {{ $log->from_email }}</div>
                                <div>To: {{ implode(', ', $log->to_emails ?? []) }}</div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-text-subtle">
                                <span class="oh-pill">{{ str_replace('_',' ', $log->status) }}</span>
                                @if ($log->needs_review)
                                    <span class="oh-pill oh-pill--warning">Needs review</span>
                                @endif
                                <span>{{ optional($log->sent_at)->diffForHumans() ?? '—' }}</span>
                            </div>
                            @if ($log->needs_review && $isTenantAdmin)
                                <form method="POST"
                                    action="{{ route('tenant.email-logs.link-contact', ['tenant' => $tenantId, 'log' => $log->id]) }}"
                                    class="flex items-center gap-2 mt-2">
                                    @csrf
                                    <select name="contact_id" class="oh-select h-9">
                                        <option value="">Link contact…</option>
                                        @foreach ($contactOptions as $contact)
                                            <option value="{{ $contact->id }}">
                                                {{ $contact->firstName }} {{ $contact->lastName }} ({{ $contact->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="oh-btn oh-btn--primary h-9 text-xs px-3">Link</button>
                                </form>
                                <form method="POST"
                                    action="{{ route('tenant.email-logs.ignore', ['tenant' => $tenantId, 'log' => $log->id]) }}"
                                    class="mt-2">
                                    @csrf
                                    <button type="submit" class="oh-btn h-9 text-xs w-full">Ignore</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-text-subtle py-6">
                            No email logs yet. Connect Gmail in Settings → Mailbox Sync.
                        </div>
                @endforelse
            </div>

            @if (method_exists($logs, 'hasPages') && $logs->hasPages())
                <div class="mt-4 text-sm text-text-subtle space-y-3">
                    <div>Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} results</div>
                    <div class="flex items-center justify-between">
                        @if ($logs->onFirstPage())
                            <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                        @else
                            <a href="{{ $logs->previousPageUrl() }}" class="oh-btn">Previous</a>
                        @endif
                        @if ($logs->hasMorePages())
                            <a href="{{ $logs->nextPageUrl() }}" class="oh-btn">Next</a>
                        @else
                            <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
