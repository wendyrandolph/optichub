@extends('layouts.trades')

@section('title', 'Trade Quote')

@section('trades-content')
    @php
        $clientName = trim(($quote->client?->firstName ?? '') . ' ' . ($quote->client?->lastName ?? '')) ?: 'Client';
        $statusLabel = ucfirst($quote->status);

        $tenantKey = $tenant->getRouteKey();
        $previewLink = $publicLink;

    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $quote->title }}</h1>
                <p class="text-sm text-text-subtle mt-1">{{ $clientName }} · {{ $statusLabel }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a class="oh-btn" href="{{ route('tenant.trades.quotes.index', ['tenant' => $tenantKey]) }}">All quotes</a>
                <button type="button" class="oh-btn" onclick="window.print()">Print</button>
                @if ($quote->status !== 'archived')
                    @if ($previewLink)
                        <a class="oh-btn" href="{{ $previewLink }}" target="_blank" rel="noopener">Preview client view</a>
                    @elseif ($quote->token_hash)
                        <span class="text-xs text-text-subtle">Send quote to generate preview link.</span>
                    @endif
                    @if (!$quote->isLocked())
                        <a class="oh-btn"
                            href="{{ route('tenant.trades.quotes.edit', ['tenant' => $tenant->id, 'quote' => $quote->id]) }}">Edit</a>
                    @endif
                    <form method="POST"
                        action="{{ route('tenant.trades.quotes.send', ['tenant' => $tenant->id, 'quote' => $quote->id]) }}">
                        @csrf
                        <button class="oh-btn oh-btn--primary" type="submit">Send quote</button>
                    </form>
                    @if ($quote->isLocked())
                        <form method="POST"
                            action="{{ route('tenant.trades.quotes.duplicate', ['tenant' => $tenant->id, 'quote' => $quote->id]) }}">
                            @csrf
                            <button class="oh-btn" type="submit">Duplicate to revise</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif
        @if (session('error_message'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error_message') }}
            </div>
        @endif
        @if ($quote->status === 'archived')
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This quote is archived and read-only.
            </div>
        @endif

        @if ($publicLink)
            <div class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                <div class="flex flex-wrap items-center gap-2">
                    <span>Public link:</span>
                    <button type="button" class="text-[rgb(var(--ui-primary))] underline" data-copy-quote-link>
                        Copy this link
                    </button>
                    <span class="text-xs text-text-subtle" data-copy-quote-status></span>
                </div>
                <input type="text" class="sr-only" value="{{ $publicLink }}" readonly data-copy-quote-value>
            </div>
        @endif

        <div class="oh-card p-5 space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-sm font-semibold text-text-base">Quote details</div>
                    <div class="text-xs text-text-subtle mt-1">
                        Status: {{ $statusLabel }}@if ($quote->sent_at)
                            · Sent {{ $quote->sent_at->format('M j, Y') }}
                        @endif
                        @if ($quote->expires_at)
                            · Expires {{ $quote->expires_at->format('M j, Y') }}
                        @endif
                    </div>
                </div>
                <div class="text-sm text-text-base font-semibold">
                    Total: ${{ number_format($quote->total, 2) }}
                </div>
            </div>

            <div class="border-t border-border-default/60 pt-3">
                <div class="text-sm font-semibold text-text-base mb-2">Line items</div>
                <div class="space-y-2">
                    @foreach ($quote->items as $item)
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <div class="text-text-base">{{ $item->description }}</div>
                                <div class="text-xs text-text-subtle">
                                    Qty {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} ·
                                    ${{ number_format($item->unit_price, 2) }}
                                </div>
                            </div>
                            <div class="text-text-base font-semibold">${{ number_format($item->line_total, 2) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-border-default/60 pt-3 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-text-subtle">Subtotal</span>
                    <span class="text-text-base font-semibold">${{ number_format($quote->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-subtle">Tax total</span>
                    <span class="text-text-base">${{ number_format($quote->tax_total, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-text-subtle">Discount</span>
                    <span class="text-text-base">${{ number_format($quote->discount_total ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-semibold">
                    <span>Total</span>
                    <span>${{ number_format($quote->total, 2) }}</span>
                </div>
            </div>

            @if ($quote->notes)
                <div class="border-t border-border-default/60 pt-3 text-sm text-text-subtle">
                    {{ $quote->notes }}
                </div>
            @endif

            @if ($quote->acceptance)
                <div class="border-t border-border-default/60 pt-3 text-sm text-text-subtle">
                    Accepted by {{ $quote->acceptance->signer_name }} on
                    {{ $quote->acceptance->accepted_at->format('M j, Y g:i A') }}.
                </div>
            @endif
        </div>

        <div class="flex items-center gap-3">
            @if ($quote->status !== 'archived')
                <form method="POST"
                    action="{{ route('tenant.trades.quotes.archive', ['tenant' => $tenant->id, 'quote' => $quote->id]) }}"
                    onsubmit="return confirm('Archive this quote?');">
                    @csrf
                    @method('PATCH')
                    <button class="oh-btn" type="submit">Archive</button>
                </form>
            @endif
            @if (!$quote->isLocked())
                <form method="POST"
                    action="{{ route('tenant.trades.quotes.destroy', ['tenant' => $tenant->id, 'quote' => $quote->id]) }}"
                    onsubmit="return confirm('Delete this quote draft?');">
                    @csrf
                    @method('DELETE')
                    <button class="oh-btn oh-btn--danger" type="submit">Delete draft</button>
                </form>
            @endif
        </div>
    </div>
    @if ($publicLink)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const copyBtn = document.querySelector('[data-copy-quote-link]');
                    const copyValue = document.querySelector('[data-copy-quote-value]');
                    const copyStatus = document.querySelector('[data-copy-quote-status]');
                    if (!copyBtn || !copyValue) return;

                    const setStatus = (text) => {
                        if (!copyStatus) return;
                        copyStatus.textContent = text;
                        if (text) {
                            setTimeout(() => {
                                copyStatus.textContent = '';
                            }, 2000);
                        }
                    };

                    copyBtn.addEventListener('click', async () => {
                        const value = copyValue.value;
                        try {
                            if (navigator.clipboard?.writeText) {
                                await navigator.clipboard.writeText(value);
                            } else {
                                copyValue.classList.remove('sr-only');
                                copyValue.select();
                                document.execCommand('copy');
                                copyValue.classList.add('sr-only');
                            }
                            setStatus('Copied.');
                        } catch (e) {
                            setStatus('Unable to copy.');
                        }
                    });
                });
            </script>
        @endpush
    @endif
@endsection
