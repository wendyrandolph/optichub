@extends('layouts.trades')

@section('title', 'Trade Quotes')

@section('trades-content')
    @php
        use Illuminate\Support\Carbon;
        use Illuminate\Support\CarbonInterval;

        $tenantKey = $tenant->getRouteKey();
        $search = $search ?? '';
        $activeStatus = $activeStatus !== '' ? $activeStatus : 'all';
        $filters = $filters ?? [];
        $kpis = $kpis ?? [];
        $tabs = [
            'all' => 'All',
            'draft' => 'Draft',
            'sent' => 'Sent',
            'viewed' => 'Viewed',
            'accepted' => 'Accepted',
            'expired' => 'Expired',
            'archived' => 'Archived',
        ];

        $rangeFrom = $kpis['range_from'] ?? null;
        $rangeTo = $kpis['range_to'] ?? null;
        $rangeLabel = $rangeFrom && $rangeTo
            ? Carbon::parse($rangeFrom)->format('M j') . ' - ' . Carbon::parse($rangeTo)->format('M j')
            : null;

        $acceptanceRate = $kpis['acceptance_rate'] ?? null;
        $avgSeconds = $kpis['avg_accept_seconds'] ?? null;
        $avgTimeLabel = $avgSeconds
            ? CarbonInterval::seconds($avgSeconds)->cascade()->forHumans(['short' => true, 'parts' => 2])
            : '—';

        $amountSent = $kpis['sent_amount'] ?? 0;
        $amountAccepted = $kpis['accepted_amount'] ?? 0;
        $amountSummary = $amountSent > 0 || $amountAccepted > 0
            ? '$' . number_format((float) $amountSent, 2) . ' sent / $' . number_format((float) $amountAccepted, 2) . ' accepted'
            : '—';

        $statusForForm = $activeStatus !== 'all' ? $activeStatus : '';
        $queryBase = request()->except(['status', 'page']);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Quotes</h1>
                <p class="text-sm text-text-subtle mt-1">Track estimate progress, follow-ups, and approvals.</p>
            </div>
            <a class="oh-btn oh-btn--primary" href="{{ route('tenant.trades.quotes.create', ['tenant' => $tenantKey]) }}">
                New quote
            </a>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="oh-card border border-border-default/60 rounded-xl px-4 py-3">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Quotes sent</p>
                    @if ($rangeLabel)
                        <span class="text-[11px] text-text-subtle">{{ $rangeLabel }}</span>
                    @endif
                </div>
                <p class="text-xl font-semibold text-text-base">{{ $kpis['sent'] ?? 0 }}</p>
                <p class="text-xs text-text-subtle mt-1">{{ $amountSummary }}</p>
            </div>
            <div class="oh-card border border-border-default/60 rounded-xl px-4 py-3">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Quotes accepted</p>
                    @if ($rangeLabel)
                        <span class="text-[11px] text-text-subtle">{{ $rangeLabel }}</span>
                    @endif
                </div>
                <p class="text-xl font-semibold text-text-base">{{ $kpis['accepted'] ?? 0 }}</p>
                <p class="text-xs text-text-subtle mt-1">Acceptance rate {{ $acceptanceRate !== null ? $acceptanceRate . '%' : '—' }}</p>
            </div>
            <div class="oh-card border border-border-default/60 rounded-xl px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Acceptance rate</p>
                <p class="text-xl font-semibold text-text-base">{{ $acceptanceRate !== null ? $acceptanceRate . '%' : '—' }}</p>
                <p class="text-xs text-text-subtle mt-1">From sent quotes in range.</p>
            </div>
            <div class="oh-card border border-border-default/60 rounded-xl px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Avg time to accept</p>
                <p class="text-xl font-semibold text-text-base">{{ $avgTimeLabel }}</p>
                <p class="text-xs text-text-subtle mt-1">Based on sent → accepted.</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $tabKey => $tabLabel)
                @php
                    $tabQuery = array_merge($queryBase, $tabKey === 'all' ? [] : ['status' => $tabKey]);
                    $tabUrl = route('tenant.trades.quotes.index', array_merge(['tenant' => $tenantKey], $tabQuery));
                @endphp
                <a href="{{ $tabUrl }}"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ $activeStatus === $tabKey ? 'border-[rgb(var(--brand-primary))] bg-[rgba(var(--brand-primary),0.12)] text-text-base' : 'border-border-default text-text-subtle hover:text-text-base hover:border-[rgba(var(--brand-primary),0.35)]' }}">
                    {{ $tabLabel }}
                </a>
            @endforeach
        </div>

        <form method="GET" class="oh-card border border-border-default/60 rounded-xl p-4 space-y-3">
            <input type="hidden" name="status" value="{{ $statusForForm }}">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-4">
                    <label class="text-xs text-text-subtle">Search</label>
                    <input type="text" name="q" value="{{ $search }}" class="oh-input h-10 w-full"
                        placeholder="Search name, email, phone, quote…">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-text-subtle">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="oh-input h-10 w-full">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-text-subtle">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="oh-input h-10 w-full">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-text-subtle">Min amount</label>
                    <input type="number" step="0.01" name="amount_min" value="{{ $filters['amount_min'] ?? '' }}"
                        class="oh-input h-10 w-full" placeholder="0.00">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-text-subtle">Max amount</label>
                    <input type="number" step="0.01" name="amount_max" value="{{ $filters['amount_max'] ?? '' }}"
                        class="oh-input h-10 w-full" placeholder="0.00">
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                    <input type="checkbox" name="site_visit" value="1" class="rounded border-border-default"
                        @checked(!empty($filters['site_visit']))>
                    Site visit required
                </label>
                <div class="flex items-center gap-2">
                    <button class="oh-btn" type="submit">Apply filters</button>
                    @if ($search !== '' || $statusForForm !== '' || !empty($filters['from']) || !empty($filters['to']) || !empty($filters['amount_min']) || !empty($filters['amount_max']) || !empty($filters['site_visit']))
                        <a class="oh-btn" href="{{ route('tenant.trades.quotes.index', ['tenant' => $tenantKey]) }}">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="hidden md:block oh-card p-0 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-muted text-text-subtle">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Quote</th>
                        <th class="px-4 py-3 text-left font-semibold">Client</th>
                        <th class="px-4 py-3 text-left font-semibold">Job</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                        <th class="px-4 py-3 text-left font-semibold">Sent</th>
                        <th class="px-4 py-3 text-left font-semibold">Expires</th>
                        <th class="px-4 py-3 text-left font-semibold">Last viewed</th>
                        <th class="px-4 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default">
                    @forelse ($quotes as $quote)
                        @php
                            $clientName = trim(($quote->client?->firstName ?? '') . ' ' . ($quote->client?->lastName ?? '')) ?: 'Client';
                            $jobSummary = $quote->job?->summary ?? '—';
                            $statusKey = $quote->status ?? 'draft';
                            $isExpired = $statusKey !== 'accepted' && $statusKey !== 'archived' && $quote->expires_at && $quote->expires_at->isPast();
                            $displayStatus = $isExpired ? 'expired' : ($statusKey === 'sent' && $quote->last_viewed_at ? 'viewed' : $statusKey);
                            $statusLabel = match ($displayStatus) {
                                'needs_site_visit' => 'Needs site visit',
                                'ready_to_send' => 'Ready to send',
                                'sent' => 'Sent',
                                'viewed' => 'Viewed',
                                'accepted' => 'Accepted',
                                'expired' => 'Expired',
                                'archived' => 'Archived',
                                'draft' => 'Draft',
                                default => ucfirst($displayStatus),
                            };
                            $amountLabel = $statusKey === 'needs_site_visit'
                                ? 'Pending'
                                : ($quote->total !== null ? '$' . number_format((float) $quote->total, 2) : '—');
                            $scheduleParams = [
                                'tenant' => $tenantKey,
                                'quote' => $quote->id,
                                'purpose' => 'site_visit',
                            ];
                            if ($quote->trade_job_id) {
                                $scheduleParams['job'] = $quote->trade_job_id;
                            }
                            $jobCreateParams = [
                                'tenant' => $tenantKey,
                                'client' => $quote->client_id,
                                'quote' => $quote->id,
                            ];
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-text-base">{{ $quote->title }}</div>
                                <div class="text-xs text-text-subtle">Updated {{ $quote->updated_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-text-base">{{ $clientName }}</div>
                                <div class="text-xs text-text-subtle">{{ $quote->client?->email ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-text-base">{{ $jobSummary }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-muted px-2.5 py-0.5 text-[11px] font-medium text-text-subtle">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-text-base">{{ $amountLabel }}</td>
                            <td class="px-4 py-3 text-xs text-text-subtle">{{ $quote->sent_at?->format('M j') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-text-subtle">{{ $quote->expires_at?->format('M j') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-text-subtle">{{ $quote->last_viewed_at?->format('M j, g:i A') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($displayStatus === 'needs_site_visit')
                                        <a class="oh-btn text-xs" href="{{ route('tenant.trades.schedule.create', $scheduleParams) }}">Schedule site visit</a>
                                    @elseif (in_array($statusKey, ['draft', 'ready_to_send'], true))
                                        <form method="POST" action="{{ route('tenant.trades.quotes.send', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">
                                            @csrf
                                            <button class="oh-btn text-xs" type="submit">Send quote</button>
                                        </form>
                                    @elseif ($statusKey === 'accepted' && !$quote->trade_job_id)
                                        <a class="oh-btn text-xs" href="{{ route('tenant.trades.jobs.create', $jobCreateParams) }}">Convert to job</a>
                                    @elseif (in_array($displayStatus, ['sent', 'viewed'], true))
                                        <a class="oh-btn text-xs" href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">Follow up</a>
                                    @endif
                                    <a class="oh-btn text-xs" href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">View</a>
                                    @if ($statusKey !== 'archived')
                                        <form method="POST"
                                            action="{{ route('tenant.trades.quotes.archive', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}"
                                            onsubmit="return confirm('Archive this quote?');">
                                            @csrf
                                            @method('PATCH')
                                            <button class="oh-btn text-xs" type="submit">Archive</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-sm text-text-subtle">
                                No quotes match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="space-y-4 md:hidden">
            @forelse ($quotes as $quote)
                @php
                    $clientName = trim(($quote->client?->firstName ?? '') . ' ' . ($quote->client?->lastName ?? '')) ?: 'Client';
                    $jobSummary = $quote->job?->summary ?? '—';
                    $statusKey = $quote->status ?? 'draft';
                    $isExpired = $statusKey !== 'accepted' && $statusKey !== 'archived' && $quote->expires_at && $quote->expires_at->isPast();
                    $displayStatus = $isExpired ? 'expired' : ($statusKey === 'sent' && $quote->last_viewed_at ? 'viewed' : $statusKey);
                    $statusLabel = match ($displayStatus) {
                        'needs_site_visit' => 'Needs site visit',
                        'ready_to_send' => 'Ready to send',
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'accepted' => 'Accepted',
                        'expired' => 'Expired',
                        'archived' => 'Archived',
                        'draft' => 'Draft',
                        default => ucfirst($displayStatus),
                    };
                    $amountLabel = $statusKey === 'needs_site_visit'
                        ? 'Pending'
                        : ($quote->total !== null ? '$' . number_format((float) $quote->total, 2) : '—');
                    $scheduleParams = [
                        'tenant' => $tenantKey,
                        'quote' => $quote->id,
                        'purpose' => 'site_visit',
                    ];
                    if ($quote->trade_job_id) {
                        $scheduleParams['job'] = $quote->trade_job_id;
                    }
                    $jobCreateParams = [
                        'tenant' => $tenantKey,
                        'client' => $quote->client_id,
                        'quote' => $quote->id,
                    ];
                @endphp
                <div class="oh-card border border-border-default/60 rounded-2xl p-4 space-y-3">
                    <div>
                        <div class="text-sm text-text-subtle">Quote</div>
                        <div class="text-base font-semibold text-text-base">{{ $quote->title }}</div>
                        <div class="text-xs text-text-subtle mt-1">Updated {{ $quote->updated_at?->diffForHumans() }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-text-base">{{ $clientName }}</div>
                        <div class="text-xs text-text-subtle">{{ $quote->client?->email ?: '—' }}</div>
                    </div>
                    <div class="text-sm text-text-base">{{ $jobSummary }}</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-muted px-2.5 py-0.5 text-[11px] font-medium text-text-subtle">
                            <span class="h-1.5 w-1.5 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                            {{ $statusLabel }}
                        </span>
                        <span class="text-sm font-semibold text-text-base">{{ $amountLabel }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-text-subtle">
                        <div>Sent: {{ $quote->sent_at?->format('M j') ?? '—' }}</div>
                        <div>Expires: {{ $quote->expires_at?->format('M j') ?? '—' }}</div>
                        <div>Viewed: {{ $quote->last_viewed_at?->format('M j, g:i A') ?? '—' }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($displayStatus === 'needs_site_visit')
                            <a class="oh-btn text-xs" href="{{ route('tenant.trades.schedule.create', $scheduleParams) }}">Schedule site visit</a>
                        @elseif (in_array($statusKey, ['draft', 'ready_to_send'], true))
                            <form method="POST" action="{{ route('tenant.trades.quotes.send', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">
                                @csrf
                                <button class="oh-btn text-xs" type="submit">Send quote</button>
                            </form>
                        @elseif ($statusKey === 'accepted' && !$quote->trade_job_id)
                            <a class="oh-btn text-xs" href="{{ route('tenant.trades.jobs.create', $jobCreateParams) }}">Convert to job</a>
                        @elseif (in_array($displayStatus, ['sent', 'viewed'], true))
                            <a class="oh-btn text-xs" href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">Follow up</a>
                        @endif
                        <a class="oh-btn text-xs" href="{{ route('tenant.trades.quotes.show', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}">View</a>
                        @if ($statusKey !== 'archived')
                            <form method="POST"
                                action="{{ route('tenant.trades.quotes.archive', ['tenant' => $tenantKey, 'quote' => $quote->id]) }}"
                                onsubmit="return confirm('Archive this quote?');">
                                @csrf
                                @method('PATCH')
                                <button class="oh-btn text-xs" type="submit">Archive</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="oh-card border border-border-default/60 rounded-2xl p-4 text-sm text-text-subtle">
                    No quotes match these filters.
                </div>
            @endforelse
        </div>

        {{ $quotes->links() }}
    </div>
@endsection
