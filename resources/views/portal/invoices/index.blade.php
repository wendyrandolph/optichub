@extends('layouts.portal') {{-- or whatever your client layout is --}}

@section('title', 'Invoices')

@section('content')
    @php
        $totalInvoices = $invoices->count();
        $openInvoices = $invoices
            ->filter(fn($invoice) => !in_array($invoice->status, ['paid', 'void'], true))
            ->count();
        $overdueInvoices = $invoices->filter(fn($invoice) => ($invoice->status ?? '') === 'overdue')->count();

        $statusTone = function ($status) {
            return match (strtolower((string) $status)) {
                'paid' => 'oh-pill oh-pill--success',
                'sent', 'pending' => 'oh-pill oh-pill--info',
                'overdue' => 'oh-pill oh-pill--danger',
                default => 'oh-pill',
            };
        };
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold text-text-base">Invoices</h1>
                <p class="text-sm text-text-subtle">View and manage your billing in one place.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-xs text-text-subtle">
                <div class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-card px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></span>
                    {{ $totalInvoices }} total
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-card px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                    {{ $openInvoices }} open
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-border-default bg-surface-card px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                    {{ $overdueInvoices }} overdue
                </div>
            </div>
        </div>

        <div class="grid gap-3 lg:grid-cols-3">
            <div class="oh-card p-5 lg:col-span-1">
                <div class="flex items-start gap-3">
                    <div class="h-10 w-10 rounded-lg bg-[rgba(var(--brand-primary),0.12)] text-[rgb(var(--brand-primary))] flex items-center justify-center">
                        <i class="fa-regular fa-credit-card text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Billing overview</h2>
                        <p class="text-xs text-text-subtle mt-1">
                            Keep track of balances, due dates, and receipts in one view.
                        </p>
                    </div>
                </div>
            </div>
            <div class="oh-card p-5 lg:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="h-10 w-10 rounded-lg bg-surface-muted text-text-subtle flex items-center justify-center">
                        <i class="fa-regular fa-file-lines text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Auto-recorded receipts</h2>
                        <p class="text-xs text-text-subtle mt-1">
                            Download PDFs for your records or share them with your accounting team.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($invoices as $invoice)
                <a href="{{ route('portal.invoices.show', $invoice) }}"
                    class="group rounded-xl border border-border-default bg-surface-card p-4 shadow-sm hover:border-[rgb(var(--brand-primary))] hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs text-text-subtle uppercase tracking-wide">Invoice</p>
                            <p class="text-sm font-semibold text-text-base">
                                {{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}
                            </p>
                        </div>
                        <span class="{{ $statusTone($invoice->status ?? 'draft') }} text-[11px]">
                            {{ ucfirst($invoice->status ?? 'draft') }}
                        </span>
                    </div>
                    <div class="mt-3 space-y-2 text-xs text-text-subtle">
                        <div class="flex items-center justify-between">
                            <span>Issued</span>
                            <span class="text-text-base">{{ optional($invoice->issue_date)->format('M j, Y') ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Due</span>
                            <span class="text-text-base">{{ optional($invoice->due_date)->format('M j, Y') ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Total</span>
                            <span class="text-text-base">${{ number_format($invoice->total_amount ?? ($invoice->balance_due ?? 0), 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs text-text-subtle">
                        <span>Open invoice</span>
                        <span class="text-text-base group-hover:text-[rgb(var(--brand-primary))]">View details →</span>
                    </div>
                </a>
            @empty
                <div class="sm:col-span-2 lg:col-span-3">
                    <div class="rounded-xl border border-border-default bg-surface-card p-6 text-sm text-text-subtle">
                        You don’t have any invoices yet. When one is issued, it will appear here with a PDF to download.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
