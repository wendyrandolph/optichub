@php
    $tenantId = request()->route('tenant');
    $currentStatus = $filters['status'] ?? 'all';
    $search = $filters['q'] ?? '';
    $sort = $filters['sort'] ?? 'recent';
@endphp
<div class="oh-page space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Billing</p>
            <h1 class="text-2xl font-semibold text-text-base">Invoices</h1>
            <p class="mt-1 text-sm text-text-subtle">Track balances, payment status, and invoice timing in one place.</p>
        </div>
        @if (Route::has($routePrefix . '.create'))
            <div class="flex items-center gap-2">
                @if (Route::has($routePrefix . '.recurring.index'))
                    <a href="{{ route($routePrefix . '.recurring.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                        Recurring
                    </a>
                @endif
                <a href="{{ route($routePrefix . '.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus text-[12px] mr-2"></i> New invoice
                </a>
            </div>
        @endif
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="oh-card p-4 border border-border-default/70">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Total invoices</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ $stats['total'] ?? 0 }}</div>
            <div class="text-xs text-text-subtle mt-1">All statuses</div>
        </div>
        <div class="oh-card p-4 border border-border-default/70">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Open balance</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ money($stats['open_balance'] ?? 0) }}</div>
            <div class="text-xs text-text-subtle mt-1">Unpaid invoices</div>
        </div>
        <div class="oh-card p-4 border border-border-default/70">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Overdue</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ $counts['overdue'] ?? 0 }}</div>
            <div class="text-xs text-text-subtle mt-1">Past due date</div>
        </div>
        <div class="oh-card p-4 border border-border-default/70">
            <div class="text-[11px] uppercase tracking-wide text-text-subtle">Paid (total)</div>
            <div class="text-2xl font-semibold text-text-base mt-1">{{ money($stats['paid_total'] ?? 0) }}</div>
            <div class="text-xs text-text-subtle mt-1">Collected to date</div>
        </div>
    </div>

    {{-- Utility bar --}}
    <div class="oh-card border border-border-default/60 p-4 md:p-5">
        <form method="GET" action="{{ route($routePrefix . '.index', ['tenant' => $tenantId]) }}" class="space-y-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1">
                <label class="sr-only" for="q">Search invoices</label>
                <input id="q" type="text" name="q" value="{{ $search }}"
                    placeholder="Search invoice #, client, amount…" class="oh-input w-full h-10"
                    aria-label="Search invoices">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="oh-btn oh-btn--primary h-10">Search</button>
                @if ($search || $currentStatus !== 'all' || $sort !== 'recent')
                    <a href="{{ route($routePrefix . '.index', ['tenant' => $tenantId]) }}" class="oh-btn h-10">
                        Reset
                    </a>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <div class="text-[11px] font-semibold tracking-wide text-text-subtle uppercase">Status</div>
                <div class="flex flex-wrap items-center gap-2 overflow-x-auto pb-1">
                @php
                    $chipMap = [
                        'all' => 'All',
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                    ];
                @endphp
                @foreach ($chipMap as $val => $label)
                    @php
                        $isActive = $currentStatus === $val;
                        $activePill =
                            $val !== 'all'
                                ? match ($val) {
                                    'paid' => 'oh-pill oh-pill--success',
                                    'overdue' => 'oh-pill oh-pill--danger',
                                    'sent' => 'oh-pill oh-pill--info',
                                    'draft' => 'oh-pill',
                                    default => 'oh-pill',
                                }
                                : 'oh-pill';
                        $inactivePill = 'oh-pill oh-pill--muted';
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['status' => $val, 'page' => null]) }}" class="inline-flex"
                        aria-pressed="{{ $isActive ? 'true' : 'false' }}" @if ($isActive) aria-current="page" @endif>
                        <span class="{{ $isActive ? $activePill : $inactivePill }}">
                            <span class="text-[11px]">{{ $label }}</span>
                            <span class="text-[11px] {{ $isActive ? 'text-text-base' : 'text-text-subtle' }}">
                                {{ $counts[$val] ?? 0 }}
                            </span>
                        </span>
                    </a>
                @endforeach
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-[11px] font-semibold tracking-wide text-text-subtle uppercase">Sort</div>
                <div class="flex flex-wrap items-center gap-2 overflow-x-auto pb-1">
                @php
                    $sortMap = [
                        'recent' => 'Recently updated',
                        'due' => 'Due soon',
                        'amount_desc' => 'Amount (high)',
                    ];
                @endphp
                @foreach ($sortMap as $val => $label)
                    @php
                        $isActive = $sort === $val;
                        $activePill = 'oh-pill oh-pill--info';
                        $inactivePill = 'oh-pill oh-pill--muted';
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['sort' => $val, 'page' => null]) }}"
                        class="inline-flex" aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        @if ($isActive) aria-current="page" @endif>
                        <span class="{{ $isActive ? $activePill : $inactivePill }}">
                            <span class="text-[11px]">{{ $label }}</span>
                        </span>
                    </a>
                @endforeach
                </div>
            </div>
        </div>
        </form>
    </div>

    {{-- Table (desktop) --}}
    <div class="oh-card border border-border-default/70 shadow-card overflow-hidden hidden md:block">
        <table class="min-w-full text-sm">
            <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                <tr class="text-left">
                    <th class="px-4 py-3 font-medium text-text-subtle w-10">#</th>
                    <th class="px-4 py-3 font-medium">Invoice #</th>
                    <th class="px-4 py-3 font-medium">Client</th>
                    <th class="px-4 py-3 font-medium">Issued</th>
                    <th class="px-4 py-3 font-medium">Due</th>
                    <th class="px-4 py-3 font-medium text-right">Amount</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($invoices as $invoice)
                    @php
                        $status = strtolower($invoice->status ?? 'draft');
                        $pill = match ($status) {
                            'paid' => 'oh-pill oh-pill--success',
                            'overdue' => 'oh-pill oh-pill--danger',
                            'sent' => 'oh-pill oh-pill--info',
                            default => 'oh-pill',
                        };
                        $client = $invoice->client;
                        $clientName = $client
                            ? trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''))
                            : null;
                    @endphp
                    <tr class="hover:bg-surface-accent/40">
                        <td class="px-4 py-3 text-text-subtle">
                            {{ method_exists($invoices, 'firstItem') && $invoices->firstItem() ? $invoices->firstItem() + $loop->index : $loop->iteration }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-text-base">
                            {{ $invoice->invoice_number ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-text-base">
                            {{ $clientName ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-text-subtle">
                            {{ $invoice->issue_date?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-text-subtle">
                            {{ $invoice->due_date?->format('M j, Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-text-base">
                            {{ money($invoice->total_amount ?? 0) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="{{ $pill }} text-[11px]">{{ ucfirst($status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route($routePrefix . '.show', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                                    class="oh-icon-btn" aria-label="View invoice">
                                    <i class="fa-regular fa-eye text-[12px]"></i>
                                </a>
                                <a href="{{ route($routePrefix . '.pdf', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                                    class="oh-icon-btn" aria-label="Download PDF" target="_blank">
                                    <i class="fa-regular fa-file-pdf text-[12px]"></i>
                                </a>
                                <form
                                    action="{{ route($routePrefix . '.send', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                                    method="POST" onsubmit="return confirm('Send this invoice?');">
                                    @csrf
                                    <button type="submit" class="oh-icon-btn" aria-label="Send invoice">
                                        <i class="fa-regular fa-paper-plane text-[12px]"></i>
                                    </button>
                                </form>
                                <a href="{{ route($routePrefix . '.edit', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                                    class="oh-icon-btn" aria-label="Edit invoice">
                                    <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                                </a>
                                <form
                                    action="{{ route($routePrefix . '.destroy', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                                    method="POST" onsubmit="return confirm('Delete this invoice?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="oh-icon-btn text-rose-600"
                                        aria-label="Delete invoice">
                                        <i class="fa-regular fa-trash-can text-[12px]"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-text-subtle">
                            <div class="space-y-2">
                                <p class="font-semibold text-text-base">No invoices yet.</p>
                                <p class="text-sm text-text-subtle">When you create your first invoice, it’ll show up
                                    here.</p>
                                @if (Route::has($routePrefix . '.create'))
                                    <a href="{{ route($routePrefix . '.create', ['tenant' => $tenantId]) }}"
                                        class="oh-btn oh-btn--primary mt-2">
                                        Create invoice
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Cards (mobile to md) --}}
    <div class="grid gap-3 md:hidden">
        @forelse ($invoices as $invoice)
            @php
                $status = strtolower($invoice->status ?? 'draft');
                $pill = match ($status) {
                    'paid' => 'oh-pill oh-pill--success',
                    'overdue' => 'oh-pill oh-pill--danger',
                    'sent' => 'oh-pill oh-pill--info',
                    default => 'oh-pill',
                };
                $client = $invoice->client;
                $clientName = $client ? trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) : '—';
            @endphp
            <article class="oh-card p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-text-base">Invoice
                            #{{ $invoice->invoice_number ?? '—' }}</div>
                        <div class="text-xs text-text-subtle">Client: {{ $clientName }}</div>
                    </div>
                    <span class="{{ $pill }} text-[11px]">{{ ucfirst($status) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-text-subtle">
                    <div>
                        <div>Issued: {{ $invoice->issue_date?->format('M j, Y') ?? '—' }}</div>
                        <div>Due: {{ $invoice->due_date?->format('M j, Y') ?? '—' }}</div>
                    </div>
                    <div class="text-right font-semibold text-text-base">{{ money($invoice->total_amount ?? 0) }}
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route($routePrefix . '.show', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                        class="oh-icon-btn" aria-label="View invoice">
                        <i class="fa-regular fa-eye text-[12px]"></i>
                    </a>
                    <a href="{{ route($routePrefix . '.pdf', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                        class="oh-icon-btn" aria-label="Download PDF" target="_blank">
                        <i class="fa-regular fa-file-pdf text-[12px]"></i>
                    </a>
                    <form action="{{ route($routePrefix . '.send', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                        method="POST" onsubmit="return confirm('Send this invoice?');">
                        @csrf
                        <button type="submit" class="oh-icon-btn" aria-label="Send invoice">
                            <i class="fa-regular fa-paper-plane text-[12px]"></i>
                        </button>
                    </form>
                    <a href="{{ route($routePrefix . '.edit', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                        class="oh-icon-btn" aria-label="Edit invoice">
                        <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                    </a>
                    <form
                        action="{{ route($routePrefix . '.destroy', ['tenant' => $tenantId, 'invoice' => $invoice]) }}"
                        method="POST" onsubmit="return confirm('Delete this invoice?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-icon-btn text-rose-600" aria-label="Delete invoice">
                            <i class="fa-regular fa-trash-can text-[12px]"></i>
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <article class="oh-card p-4 text-center text-text-subtle">
                <p class="font-semibold text-text-base">No invoices yet.</p>
                <p class="text-sm">When you create your first invoice, it’ll show up here.</p>
                @if (Route::has($routePrefix . '.create'))
                    <a href="{{ route($routePrefix . '.create', ['tenant' => $tenantId]) }}"
                        class="oh-btn oh-btn--primary mt-2">
                        Create invoice
                    </a>
                @endif
            </article>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if (method_exists($invoices, 'links'))
        @php $pager = $invoices->appends(request()->only(['q', 'status', 'sort'])); @endphp
        @if ($pager->hasPages())
            <div class="text-sm text-text-subtle space-y-3">
                <div>
                    Showing {{ $pager->firstItem() }} to {{ $pager->lastItem() }} of {{ $pager->total() }} results
                </div>
                <div class="flex items-center justify-between">
                    @if ($pager->onFirstPage())
                        <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                    @else
                        <a href="{{ $pager->previousPageUrl() }}" class="oh-btn">Previous</a>
                    @endif
                    @if ($pager->hasMorePages())
                        <a href="{{ $pager->nextPageUrl() }}" class="oh-btn">Next</a>
                    @else
                        <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
