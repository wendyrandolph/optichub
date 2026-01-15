@extends('layouts.portal')

@section('content')
    <div class="oh-page space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-sm text-text-subtle">
                    {{ $tenant->name ?? 'Renlo' }}
                </div>
                <h1 class="text-2xl font-semibold text-text-base">
                    Invoice #{{ $invoice->number }}
                </h1>
                <p class="mt-1 text-sm text-text-subtle">
                    @if ($invoice->issue_date)
                        Issued {{ $invoice->issue_date?->format('M j, Y') }}
                    @endif

                    @if ($invoice->due_date)
                        · Due {{ $invoice->due_date?->format('M j, Y') }}
                    @endif
                </p>

            </div>

            <div class="text-right space-y-2">
                @php
                    $allowPartial = $tenant?->allow_partial_payments ?? true;
                @endphp
                <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
    @if ($invoice->is_paid) bg-emerald-50 text-emerald-700
    @elseif($invoice->status === 'overdue')
        bg-rose-50 text-rose-700
    @else
        bg-amber-50 text-amber-700 @endif
">
                    {{ strtoupper($invoice->status_label) }}
                </span>

                <div class="text-sm text-text-subtle">
                    Balance due
                </div>
                <div class="text-2xl font-semibold text-text-base">
                    {{ money($invoice->balance_due) }}
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('portal.invoices.download', $invoice) }}"
                        class="oh-btn">
                        PDF
                    </a>
                    @if (!$invoice->is_paid)
                        <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}"
                            class="inline-flex items-center gap-2">
                            @csrf
                            @if ($allowPartial)
                                <input type="number" step="0.01" min="0.01" name="amount"
                                    value="{{ $invoice->balance_due }}" class="w-28 rounded-md border border-border-default px-2 py-2 text-xs">
                            @endif
                            <button type="submit"
                                class="oh-btn oh-btn--primary">
                                Pay now
                            </button>
                        </form>
                    @endif
                </div>
                @if (!$invoice->is_paid && !$allowPartial)
                    <div class="text-xs text-text-subtle">Full payment required.</div>
                @endif
            </div>
        </div>

        {{-- Line items + totals --}}
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @php
                    $lineItems = $invoice->lineItems ?? $invoice->items ?? collect();
                @endphp
                <div class="overflow-hidden rounded-xl border border-border-default bg-surface-card shadow-sm">
                    <table class="min-w-full divide-y divide-border-default text-sm">
                        <thead class="bg-surface-accent">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-text-subtle">Description</th>
                                <th class="px-4 py-3 text-right font-medium text-text-subtle">Qty</th>
                                <th class="px-4 py-3 text-right font-medium text-text-subtle">Rate</th>
                                <th class="px-4 py-3 text-right font-medium text-text-subtle">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-default bg-surface-card">
                            @forelse($lineItems as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-text-base">{{ $item->description }}</div>
                                        @if ($item->note)
                                            <div class="text-xs text-text-subtle">{{ $item->note }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-text-base">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right text-text-base">{{ money($item->rate) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-text-base">
                                        {{ money($item->total) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-text-subtle">
                                        No line items found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($invoice->notes)
                    <div class="mt-4 rounded-lg border border-border-default bg-surface-muted p-4 text-sm text-text-subtle">
                        {!! nl2br(e($invoice->notes)) !!}
                    </div>
                @endif
            </div>

            {{-- Totals --}}
            <div class="space-y-4">
                <div class="rounded-xl border border-border-default bg-surface-card p-4 shadow-sm text-sm">
                    <dl class="space-y-2">
                        {{-- Subtotal --}}
                        <div class="flex justify-between">
                            <dt class="text-text-subtle">Subtotal</dt>
                            <dd class="text-text-base">
                                {{ money($invoice->subtotal_resolved) }}
                            </dd>
                        </div>

                        {{-- Tax breakdown (if any) --}}
                        @foreach ($invoice->tax_breakdown_resolved as $tax)
                            <div class="flex justify-between">
                                <dt class="text-text-subtle">
                                    {{ $tax['label'] ?? 'Tax' }}
                                    @if (isset($tax['rate']))
                                        <span class="text-xs text-text-subtle">
                                            ({{ number_format($tax['rate'] * 100, 2) }}%)
                                        </span>
                                    @endif
                                </dt>
                                <dd class="text-text-base">
                                    {{ money($tax['amount'] ?? 0) }}
                                </dd>
                            </div>
                        @endforeach

                        {{-- Tax total (only if > 0) --}}
                        @if ($invoice->tax_total > 0)
                            <div class="flex justify-between">
                                <dt class="text-text-subtle">Tax total</dt>
                                <dd class="text-text-base">
                                    {{ money($invoice->tax_total) }}
                                </dd>
                            </div>
                        @endif

                        {{-- Divider --}}
                        <div class="border-t border-border-default my-2">
                            <div class="flex justify-between">
                                <dt class="text-text-base font-medium">Invoice total</dt>
                                <dd class="text-text-base font-semibold">
                                    {{ money($invoice->total_amount) }}
                                </dd>
                            </div>


                            {{-- Paid + balance --}}
                            <div class="flex justify-between">
                                <dt class="text-text-subtle">Paid</dt>
                                <dd class="text-text-base">
                                    {{ money($invoice->amount_paid) }}
                                </dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-text-base font-medium">Balance due</dt>
                                <dd class="text-[rgb(var(--status-danger))] font-semibold">
                                    {{ money($invoice->balance_due) }}
                                </dd>
                            </div>
                    </dl>
                </div>

                @if (!empty($manualMethods) && $manualMethods->count() > 0)
                    <div class="rounded-xl border border-border-default bg-surface-card p-4 shadow-sm">
                        <div class="text-sm font-semibold text-text-base">Payment methods</div>
                        <div class="text-sm text-text-subtle mt-1">
                            Use any method below to complete your payment.
                        </div>
                        <div class="mt-3 space-y-3 text-sm">
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
