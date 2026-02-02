@extends('layouts.portal')

@section('content')
    @php
        $allowPartial = $tenant?->allow_partial_payments ?? true;
        $status = strtolower((string) ($invoice->status ?? 'draft'));
        $pill = match ($status) {
            'paid' => 'oh-pill oh-pill--success',
            'overdue' => 'oh-pill oh-pill--danger',
            'sent' => 'oh-pill oh-pill--info',
            default => 'oh-pill',
        };
        $invoiceNumber = $invoice->invoice_number ?? $invoice->number ?? $invoice->id;
        $lineItems = $invoice->lineItems ?? $invoice->items ?? collect();
    @endphp

    <div class="oh-page space-y-6">
        <div class="oh-card p-5 border border-border-default bg-surface-card shadow-card space-y-3">
            <div class="flex items-center justify-between">
                <a href="{{ route('portal.invoices.index') }}"
                    class="oh-link-underline inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                    <i class="fa-solid fa-arrow-left text-[10px] mr-2"></i> Back to invoices
                </a>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('portal.invoices.download', $invoice) }}">
                        @csrf
                        <button type="submit" class="oh-btn oh-btn--secondary">
                            <i class="fa-regular fa-file-pdf text-[12px] mr-1"></i>PDF
                        </button>
                    </form>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] uppercase tracking-wider text-text-subtle">Invoice</p>
                    <h1 class="text-2xl font-semibold text-text-base">Invoice #{{ $invoiceNumber }}</h1>
                    <p class="text-sm text-text-subtle">{{ $tenant->name ?? 'Renlo' }}</p>
                    <p class="text-xs text-text-subtle">
                        Issued {{ $invoice->issue_date?->format('M j, Y') ?? '—' }}
                        @if ($invoice->due_date)
                            • Due {{ $invoice->due_date?->format('M j, Y') }}
                        @endif
                    </p>
                </div>
                <div class="text-right space-y-2">
                    <span class="{{ $pill }} text-[11px] px-3 py-1">{{ ucfirst($status) }}</span>
                    <div>
                        <p class="text-xs text-text-subtle">Balance due</p>
                        <p class="text-2xl font-semibold text-text-base">{{ money($invoice->balance_due) }}</p>
                    </div>
                    <div class="flex justify-end gap-2 flex-wrap">
                        @if (!$invoice->is_paid && ($stripeAccount?->status === 'active'))
                            <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}"
                                class="inline-flex items-center gap-2">
                                @csrf
                                @if ($allowPartial)
                                    <input type="number" step="0.01" min="0.01" name="amount"
                                        value="{{ $invoice->balance_due }}"
                                        class="w-28 rounded-md border border-border-default px-2 py-2 text-xs">
                                @endif
                                <button type="submit" class="oh-btn oh-btn--primary">
                                    Pay invoice
                                </button>
                            </form>
                        @elseif (!$invoice->is_paid && !empty($invoice->stripe_link))
                            <a href="{{ $invoice->stripe_link }}" target="_blank" rel="noopener"
                                class="oh-btn oh-btn--primary">
                                Pay now
                            </a>
                        @elseif (!$invoice->is_paid)
                            <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}"
                                class="inline-flex items-center gap-2">
                                @csrf
                                @if ($allowPartial)
                                    <input type="number" step="0.01" min="0.01" name="amount"
                                        value="{{ $invoice->balance_due }}"
                                        class="w-28 rounded-md border border-border-default px-2 py-2 text-xs">
                                @endif
                                <button type="submit" class="oh-btn oh-btn--primary">
                                    Mark paid
                                </button>
                            </form>
                        @endif
                    </div>
                    @if (!$invoice->is_paid && ($stripeAccount?->status !== 'active') && empty($invoice->stripe_link) && !$allowPartial)
                        <p class="text-xs text-text-subtle">Full payment required.</p>
                    @endif
                </div>
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
                            @forelse ($lineItems as $item)
                                @php
                                    $itemName = $item->name ?? $item->description ?? 'Line item';
                                    $itemDesc = $item->description ?? $item->note ?? null;
                                    $itemQty = $item->quantity ?? 0;
                                    $itemRate = $item->unit_price ?? $item->rate ?? 0;
                                    $itemTotal = $item->line_total ?? $item->total ?? 0;
                                @endphp
                                <tr>
                                    <td class="px-4 py-2">
                                        <div class="font-semibold text-text-base">{{ $itemName }}</div>
                                        @if ($itemDesc && $itemDesc !== $itemName)
                                            <div class="text-xs text-text-subtle">{{ $itemDesc }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right text-text-subtle">{{ $itemQty }}</td>
                                    <td class="px-4 py-2 text-right text-text-subtle">{{ money($itemRate) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-text-base">{{ money($itemTotal) }}</td>
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
                        <span class="text-text-base font-semibold">{{ money($invoice->subtotal_resolved) }}</span>
                    </div>
                    @foreach ($invoice->tax_breakdown_resolved as $tax)
                        <div class="flex justify-between">
                            <span class="text-text-subtle">
                                {{ $tax['label'] ?? 'Tax' }}
                                @if (isset($tax['rate']))
                                    <span class="text-xs text-text-subtle">({{ number_format($tax['rate'] * 100, 2) }}%)</span>
                                @endif
                            </span>
                            <span class="text-text-base">{{ money($tax['amount'] ?? 0) }}</span>
                        </div>
                    @endforeach
                    @if ($invoice->tax_total > 0)
                        <div class="flex justify-between">
                            <span class="text-text-subtle">Tax total</span>
                            <span class="text-text-base">{{ money($invoice->tax_total) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Paid</span>
                        <span class="text-text-base">{{ money($invoice->amount_paid) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold">
                        <span>Total</span>
                        <span>{{ money($invoice->total_amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-text-subtle">Balance due</span>
                        <span class="text-rose-600 font-semibold">{{ money($invoice->balance_due) }}</span>
                    </div>
                </div>

                @if (!empty($manualMethods) && $manualMethods->count() > 0)
                    <div class="oh-card p-4 border border-border-default/70 shadow-sm bg-surface-accent text-xs text-text-base space-y-2">
                        <p class="font-semibold text-text-base">Payment methods</p>
                        <p class="text-text-subtle">Use any method below to complete your payment.</p>
                        <div class="mt-2 space-y-3 text-sm">
                            @foreach ($manualMethods as $method)
                                <div class="rounded-lg border border-border-default/60 bg-surface-muted/40 p-3">
                                    <div class="font-medium text-text-base">
                                        {{ $method->label ?: 'Payment method' }}
                                    </div>
                                    @if (!empty($method->external_url))
                                        <a href="{{ $method->external_url }}" target="_blank" rel="noopener"
                                            class="inline-flex items-center gap-2 text-[rgb(var(--brand-primary))] hover:text-[rgb(var(--brand-secondary))] text-xs mt-2">
                                            <span>Pay now</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                        </a>
                                    @endif
                                    @if (!empty($method->instructions))
                                        <div class="text-xs text-text-subtle mt-2">
                                            {!! nl2br(e($method->instructions)) !!}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
