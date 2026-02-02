@extends('layouts.trades')

@section('title', 'Trade Job')

@section('trades-content')
    @php
        $clientName = trim(($job->client->firstName ?? '') . ' ' . ($job->client->lastName ?? '')) ?: 'Client';
        $companyName = $job->company?->company_name;
        $location = $job->serviceLocation;
        $locationLabel = $location?->label ?: $location?->address_line1 ?: 'Unassigned';
        $statusLabel = ucfirst($job->status);
        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');
        $warrantyScope = $tenant->trades_warranty_scope ?? 'job';
        $isFieldTech = auth()->user()?->isTech() ?? false;
        $role = strtolower((string) (auth()->user()?->role ?? ''));
        $isAdminRole = in_array($role, ['admin', 'super_admin', 'superadmin', 'provider'], true);
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $job->summary }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    {{ $clientName }}@if ($companyName)
                        · {{ $companyName }}
                    @endif · {{ ucfirst($job->type) }} job
                </p>
            </div>
            <div class="flex items-center gap-2">
                @unless ($isFieldTech)
                    <a href="{{ route('tenant.trades.invoices.create', ['tenant' => $tenantKey, 'contact_id' => $job->client_id, 'service_location_id' => $job->service_location_id, 'trade_job_id' => $job->id]) }}"
                        class="oh-btn">Create invoice</a>
                    <a href="{{ route('tenant.trades.jobs.edit', ['tenant' => $tenantKey, 'job' => $job->id]) }}"
                        class="oh-btn">Edit</a>
                    <a class="oh-btn"
                        href="{{ route('tenant.trades.jobs.chat', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                        <i class="fa-solid fa-comments text-[12px]"></i>
                        Job chat
                    </a>
                    @if ($isAdminRole)
                        <form method="POST"
                            action="{{ route('tenant.trades.jobs.destroy', ['tenant' => $tenantKey, 'job' => $job->id]) }}"
                            onsubmit="return confirm('Delete this job? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button class="oh-btn oh-btn--danger" type="submit">Delete Job</button>
                        </form>
                    @endif
                @endunless
                <a href="{{ route('tenant.trades.jobs.index', ['tenant' => $tenantKey]) }}" class="oh-btn">All jobs</a>
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif
        @if (session('error_message'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error_message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="oh-card p-5 lg:col-span-2 space-y-5">
                <div class="flex flex-wrap items-center gap-2 text-xs text-text-subtle">
                    <span class="oh-pill oh-pill--muted">{{ $statusLabel }}</span>
                    <span>Location: {{ $locationLabel }}</span>
                </div>
                <div class="text-sm text-text-base">
                    {{ $job->description ?: 'No description yet.' }}
                </div>

                @if ($warrantyScope === 'job')
                    <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm">
                        <div class="font-semibold text-text-base">Warranty</div>
                        <div class="text-text-subtle mt-1">
                            @if ($job->warranty_ends_on)
                                Ends {{ $job->warranty_ends_on->format('M j, Y') }}
                            @else
                                No warranty date set.
                            @endif
                        </div>
                        @if ($job->warranty_terms)
                            <div class="text-text-subtle mt-1">{{ $job->warranty_terms }}</div>
                        @endif
                    </div>
                @endif

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-text-base">Line items</div>
                    <div class="overflow-hidden rounded-xl border border-border-default/70">
                        <table class="min-w-full text-sm">
                            <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Item</th>
                                    <th class="px-3 py-2 text-right font-medium w-24">Qty</th>
                                    <th class="px-3 py-2 text-right font-medium w-32">Rate</th>
                                    @if ($warrantyScope === 'line_item')
                                        <th class="px-3 py-2 text-left font-medium w-40">Warranty</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($job->items as $item)
                                    <tr class="border-t border-border-default/60">
                                        <td class="px-3 py-2">{{ $item->description }}</td>
                                        <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                                        <td class="px-3 py-2 text-right">${{ number_format($item->unit_price ?? 0, 2) }}
                                        </td>
                                        @if ($warrantyScope === 'line_item')
                                            <td class="px-3 py-2 text-text-subtle">
                                                @if ($item->warranty_ends_on)
                                                    Ends {{ $item->warranty_ends_on->format('M j, Y') }}
                                                @else
                                                    —
                                                @endif
                                                @if ($item->warranty_terms)
                                                    <div class="text-xs">{{ $item->warranty_terms }}</div>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr class="border-t border-border-default/60">
                                        <td class="px-3 py-3 text-text-subtle" colspan="4">No line items yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-sm font-semibold text-text-base">Checklist</div>
                    @if ($job->checklistItems->isEmpty())
                        <div class="text-sm text-text-subtle">No checklist items yet.</div>
                    @else
                        <div class="space-y-2">
                            @foreach ($job->checklistItems as $item)
                                <div
                                    class="flex items-center justify-between rounded-lg border border-border-default/60 px-3 py-2">
                                    <div class="text-sm text-text-base">{{ $item->label }}</div>
                                    <span class="text-xs text-text-subtle">
                                        {{ $item->is_required ? 'Required' : 'Optional' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @php
                    $nextAppt = $job->nextAppointment;
                    $isScheduled = (bool) $nextAppt;
                    $nextLabel = $isScheduled ? $nextAppt->start_at?->timezone($tz)->format('M j, g:i A') : null;
                    $endLabel =
                        $isScheduled && $nextAppt?->end_at ? $nextAppt->end_at->timezone($tz)->format('g:i A') : null;
                    $assignments = $isScheduled ? $nextAppt->assignments ?? collect() : collect();
                    $techCount = $isScheduled ? (int) ($nextAppt->assignments_count ?? $assignments->count()) : 0;
                    $techNames = $assignments
                        ->map(fn($assignment) => $assignment->user?->name)
                        ->filter()
                        ->values()
                        ->implode(', ');
                @endphp
                <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm">
                    @if ($isScheduled)
                        <div class="font-semibold text-text-base">Next appointment</div>
                        <div class="text-text-subtle mt-1">
                            {{ $nextLabel }}@if ($endLabel)
                                – {{ $endLabel }}
                            @endif
                        </div>
                        <div class="text-text-subtle mt-1">
                            {{ $techCount }} tech(s)
                            @if ($techNames)
                                · {{ $techNames }}
                            @endif
                        </div>
                    @else
                        <div class="text-text-subtle">No appointment scheduled yet.</div>
                        @unless ($isFieldTech)
                            <a class="oh-btn text-xs mt-2"
                                href="{{ route('tenant.trades.schedule.create', ['tenant' => $tenantKey, 'job' => $job->id]) }}">
                                Schedule appointment
                            </a>
                        @endunless
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <div class="oh-card p-5">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Service location</div>
                    <div class="text-sm text-text-base mt-2">{{ $locationLabel }}</div>
                    <div class="text-xs text-text-subtle mt-1">
                        {{ $location?->address_line1 ?? 'Add a location to see address details.' }}
                    </div>
                </div>

                <div class="oh-card p-5">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Invoices</div>
                    @if ($job->invoices->isEmpty())
                        <div class="text-sm text-text-subtle mt-2">No invoices linked yet.</div>
                    @else
                        <div class="space-y-2 mt-2">
                            @foreach ($job->invoices as $invoice)
                                <a href="{{ route('tenant.trades.invoices.show', ['tenant' => $tenantKey, 'invoice' => $invoice->id]) }}"
                                    class="flex items-center justify-between rounded-lg border border-border-default/60 px-3 py-2 text-sm">
                                    <span>#{{ $invoice->invoice_number ?? $invoice->id }}</span>
                                    <span
                                        class="text-text-subtle">${{ number_format($invoice->total_amount ?? 0, 2) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="oh-card p-5">
                    <div class="text-[11px] uppercase tracking-wide text-text-subtle">Service history</div>
                    @if (empty($history) || $history->isEmpty())
                        <div class="text-sm text-text-subtle mt-2">No prior jobs for this client yet.</div>
                    @else
                        <div class="space-y-2 mt-2">
                            @foreach ($history as $past)
                                <a href="{{ route('tenant.trades.jobs.show', ['tenant' => $tenantKey, 'job' => $past->id]) }}"
                                    class="block rounded-lg border border-border-default/60 px-3 py-2 text-sm">
                                    <div class="font-medium text-text-base">{{ $past->summary }}</div>
                                    <div class="text-xs text-text-subtle">{{ ucfirst($past->status) }} ·
                                        {{ $past->updated_at->format('M j, Y') }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
