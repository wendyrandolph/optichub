@extends('layouts.app')

@section('title', 'Email Log')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Emails</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Email log</h1>
                    <p class="text-sm text-text-subtle mt-1">Outbound emails sent from your workspace.</p>
                </div>
            </div>
        </div>

        <div class="oh-card border border-border-default/60 rounded-xl p-4 md:p-5 space-y-4">
            <form method="GET" class="flex flex-col gap-3">
                <div class="grid gap-3 md:grid-cols-4 items-end">
                    <div class="md:col-span-2">
                        <label class="text-xs text-text-subtle">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="oh-input w-full h-10" placeholder="Subject or recipient...">
                    </div>
                    <div>
                        <label class="text-xs text-text-subtle">Status</label>
                        <select name="status" class="oh-select w-full h-10">
                            <option value="">All</option>
                            <option value="queued" @selected(request('status')==='queued')>Queued</option>
                            <option value="sent" @selected(request('status')==='sent')>Sent</option>
                            <option value="failed" @selected(request('status')==='failed')>Failed</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                        <a href="{{ route('tenant.email-logs.index', ['tenant' => $tenantId]) }}" class="oh-btn">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="oh-card border border-border-default/60 rounded-xl p-0 overflow-hidden hidden md:block">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-text-subtle bg-surface-muted/60">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Recipient</th>
                            <th class="px-4 py-2 text-left font-medium">Subject</th>
                            <th class="px-4 py-2 text-left font-medium">Type</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Time</th>
                            <th class="px-4 py-2 text-left font-medium">Related</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-surface-accent/40 transition">
                                <td class="px-4 py-3">{{ $log->to_email }}</td>
                                <td class="px-4 py-3">{{ $log->subject ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $log->relatedLabel() }}</td>
                                <td class="px-4 py-3">
                                    <span class="oh-pill">{{ $log->statusLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 text-text-subtle">
                                    {{ $log->sent_at?->format('M j, Y g:i A') ?? $log->queued_at?->format('M j, Y g:i A') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($url = $log->relatedUrl($tenant))
                                        <a class="oh-btn oh-btn--ghost" href="{{ $url }}">View</a>
                                    @else
                                        <span class="text-text-subtle">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-text-subtle">
                                    No outbound emails yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="md:hidden grid gap-3">
            @forelse ($logs as $log)
                <div class="oh-card border border-border-default/60 rounded-lg p-3 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-semibold text-text-base truncate">{{ $log->subject ?? 'Email' }}</div>
                        <span class="oh-pill text-xs">{{ $log->statusLabel() }}</span>
                    </div>
                    <div class="text-xs text-text-subtle">{{ $log->to_email }}</div>
                    <div class="text-xs text-text-subtle">
                        {{ $log->sent_at?->format('M j, Y g:i A') ?? $log->queued_at?->format('M j, Y g:i A') ?? '—' }}
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-text-subtle">{{ $log->relatedLabel() }}</span>
                        @if ($url = $log->relatedUrl($tenant))
                            <a class="oh-btn oh-btn--ghost" href="{{ $url }}">View</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="oh-card border border-border-default/60 rounded-lg p-6 text-center text-text-subtle">
                    No outbound emails yet.
                </div>
            @endforelse
        </div>

        {{ $logs->links() }}
    </div>
@endsection
