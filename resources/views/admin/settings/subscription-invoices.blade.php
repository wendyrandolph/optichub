@extends('layouts.app')
@section('title', 'Subscription Invoices')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Subscription Invoices</h1>
                    <p class="text-sm text-text-subtle mt-1">Receipts and billing history for your Renlo plan.</p>
                </div>
                <a href="{{ route('tenant.settings.billing', ['tenant' => $tenantId]) }}"
                    class="oh-btn">Back to Billing</a>
            </div>
        </div>

        {{-- Invoices --}}
        <div class="oh-card border border-border-default/60 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default/60 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-text-base">History</h2>
            </div>
            <div class="p-4">
                @forelse ($invoices as $invoice)
                    <div class="flex items-center justify-between py-3 border-b last:border-0 border-border-default/60">
                        <div>
                            <p class="text-sm font-semibold text-text-base">{{ $invoice['number'] ?? 'Invoice' }}</p>
                            <p class="text-xs text-text-subtle">
                                {{ $invoice['date'] ?? '—' }}
                                @if (!empty($invoice['status']))
                                    · <span class="oh-pill">{{ $invoice['status'] }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-text-base">{{ $invoice['amount'] ?? '$0.00' }}</p>
                            @if (!empty($invoice['url']))
                                <a href="{{ $invoice['url'] }}" class="text-xs text-brand-primary hover:underline" target="_blank">
                                    Download
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-text-subtle">No subscription invoices yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
