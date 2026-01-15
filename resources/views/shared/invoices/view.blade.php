@php
    $status = strtolower($invoice->status ?? 'draft');
    $pill = match ($status) {
        'paid' => 'oh-pill oh-pill--success',
        'overdue' => 'oh-pill oh-pill--danger',
        'sent' => 'oh-pill oh-pill--info',
        default => 'oh-pill',
    };
    $clientName = $invoice->client ? trim(($invoice->client->firstName ?? '') . ' ' . ($invoice->client->lastName ?? '')) : '—';
@endphp
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="oh-card p-5 border border-border-default bg-surface-card shadow-card space-y-3">
            <div class="flex items-center justify-between">
                <a href="{{ route($routePrefix . '.index', ['tenant' => request()->route('tenant')]) }}"
                    class="oh-link-underline inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                    <i class="fa-solid fa-arrow-left text-[10px] mr-2"></i> Back to invoices
                </a>
                <div class="flex items-center gap-2">
                    <button type="button" class="oh-btn oh-btn--secondary" onclick="window.print()">
                        <i class="fa-solid fa-print text-[12px] mr-1"></i>Print
                    </button>
                    <a href="{{ route($routePrefix . '.edit', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                        class="oh-btn oh-btn--secondary">
                        <i class="fa-regular fa-pen-to-square text-[12px] mr-1"></i>Edit
                    </a>
                    @if (Route::has($routePrefix . '.pdf'))
                        <a href="{{ route($routePrefix . '.pdf', ['tenant' => request()->route('tenant'), 'invoice' => $invoice]) }}"
                            class="oh-btn oh-btn--secondary" target="_blank">
                            <i class="fa-regular fa-file-pdf text-[12px] mr-1"></i>PDF
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] uppercase tracking-wider text-text-subtle">Invoice</p>
                    <h1 class="text-2xl font-semibold text-text-base">Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</h1>
                    <p class="text-sm text-text-subtle">Client: {{ $clientName }}</p>
                    <p class="text-xs text-text-subtle">
                        Issued {{ $invoice->issue_date?->format('M j, Y') ?? '—' }}
                        @if ($invoice->due_date)
                            • Due {{ $invoice->due_date?->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                <span class="{{ $pill }} text-[11px] px-3 py-1">{{ ucfirst($status) }}</span>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(260px,1fr)]">
            <div class="oh-card p-6 border border-border-default shadow-card space-y-4">
                <h3 class="text-sm font-semibold text-text-base">Line items</h3>
                <div class="overflow-hidden rounded-xl border border-border-default/70">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Item</th>
                                <th class="px-4 py-2 text-right font-medium w-20">Qty</th>
                                <th class="px-4 py-2 text-right font-medium w-28">Rate</th>
                                <th class="px-4 py-2 text-right font-medium w-28">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border)/.35);">
                            @forelse ($invoice->lineItems as $item)
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-semibold text-text-base">{{ $item->name }}</div>
                                        @if ($item->description)
                                            <div class="text-xs text-text-subtle">{{ $item->description }}</div>
                                        @endif
                                        @if ($item->source_type === 'time_entry')
                                            <span class="oh-pill oh-pill--info text-[11px] mt-1 inline-flex items-center gap-1">Time entry</span>
                                        @elseif ($item->source_type === 'time_entry_group')
                                            <span class="oh-pill text-[11px] mt-1 inline-flex items-center gap-1">Time group</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-text-subtle">{{ $item->quantity ?? 0 }}</td>
                                    <td class="px-4 py-2 text-right text-text-subtle">{{ money($item->unit_price ?? 0) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-text-base">{{ money($item->line_total ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-text-subtle">No line items yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($invoice->notes)
                    <div class="rounded-xl bg-surface-accent px-4 py-3 text-xs text-text-subtle">
                        <p class="font-semibold text-text-base mb-1">Notes</p>
                        <p>{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="oh-card p-5 border border-border-default shadow-card space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Subtotal</span>
                        <span class="text-text-base font-semibold">{{ money($invoice->subtotal ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Tax ({{ $invoice->tax_rate ?? 0 }}%)</span>
                        <span class="text-text-base">{{ money($invoice->tax_total ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Discount</span>
                        <span class="text-text-base">
                            {{ $invoice->discount_type === 'percent' ? ($invoice->discount_value ?? 0) . '%' : money($invoice->discount_value ?? 0) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <span>Total</span>
                        <span>{{ money($invoice->total ?? $invoice->total_amount ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Balance due</span>
                        <span class="text-rose-600 font-semibold">{{ money($invoice->balance_due ?? $invoice->total ?? 0) }}</span>
                    </div>
                </div>

                @if ($invoice->stripe_link)
                    <div class="oh-card p-4 border border-border-default/70 shadow-sm bg-surface-accent text-xs text-text-base space-y-2">
                        <p class="font-semibold text-text-base">Payment link</p>
                        <p class="text-text-subtle">You can send this link directly to the client if needed.</p>
                        <a href="{{ $invoice->stripe_link }}" target="_blank" class="oh-btn oh-btn--primary oh-btn--sm">
                            Open Stripe payment page
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
