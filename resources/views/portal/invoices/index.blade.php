@extends('layouts.portal') {{-- or whatever your client layout is --}}

@section('title', 'Invoices')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-[11px] uppercase tracking-[0.12em] text-text-subtle mb-1">Billing</p>
                <h1 class="text-2xl font-semibold text-text-base">Invoices</h1>
                <p class="text-sm text-text-subtle">
                    View your invoices and download receipts.
                </p>
            </div>

            <a href="{{ route('portal.dashboard') }}" class="oh-btn oh-btn--ghost text-xs sm:text-sm">
                ← Back to dashboard
            </a>
        </div>

        <div class="rounded-2xl border border-border-default bg-surface-card shadow-card overflow-hidden">
            <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-border-default/70">
                <h2 class="text-sm font-semibold text-text-base">Your invoices</h2>
                <span class="text-xs text-text-subtle">
                    {{ $invoices->count() }} total
                </span>
            </div>

            @if ($invoices->isEmpty())
                <div class="px-4 sm:px-6 py-6 text-sm text-text-subtle">
                    You don’t have any invoices yet. When your provider issues one,
                    it will appear here and you’ll be able to download the PDF.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-surface-accent border-b border-border-default/70">
                            <tr>
                                <th class="px-4 sm:px-6 py-2 text-left text-xs font-semibold text-text-subtle">Invoice #
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-text-subtle">Issued</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-text-subtle">Due</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-text-subtle">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-text-subtle">Status</th>
                                <th class="px-4 sm:px-6 py-2 text-right text-xs font-semibold text-text-subtle">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr class="border-t border-border-default/60">
                                    <td class="px-4 sm:px-6 py-2 whitespace-nowrap">
                                        {{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        {{ optional($invoice->issue_date)->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        {{ optional($invoice->due_date)->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        ${{ number_format($invoice->total_amount ?? ($invoice->balance_due ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                        @class([
                                            'bg-emerald-50 text-emerald-700' => $invoice->status === 'paid',
                                            'bg-amber-50 text-amber-700' => $invoice->status === 'sent',
                                            'bg-rose-50 text-rose-700' => $invoice->status === 'overdue',
                                            'bg-surface-muted text-text-subtle' => !in_array($invoice->status, [
                                                'paid',
                                                'sent',
                                                'overdue',
                                            ]),
                                        ])">
                                            {{ ucfirst($invoice->status ?? 'draft') }}
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-2 text-right whitespace-nowrap">
                                        <a href="{{ route('portal.invoices.show', $invoice) }}"
                                            class="text-brand-primary hover:underline text-xs sm:text-sm mr-3">
                                            View
                                        </a>
                                        <a href="{{ route('portal.invoices.pdf', $invoice) }}"
                                            class="text-text-subtle hover:text-brand-primary hover:underline text-xs sm:text-sm">
                                            PDF
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
