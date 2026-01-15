@extends('layouts.public-trades')

@section('title', 'Quote')

@section('content')
    @php
        $clientName = trim(($quote->client?->firstName ?? '') . ' ' . ($quote->client?->lastName ?? '')) ?: 'Customer';
        $statusLabel = ucfirst($quote->status);
        $brandName = $brand['name'] ?? config('app.name', 'Renlo');
        $brandColor = $brand['primary'] ?? '#0B1F52';
        $supportEmail = $brand['support_email'] ?? null;
        $supportPhone = $brand['support_phone'] ?? null;
        $supportLocation = $brand['location'] ?? null;
        $issueDate = $quote->issued_at ?? $quote->sent_at ?? $quote->created_at;
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-2">
                <p class="text-xs uppercase tracking-wide text-text-subtle">Quote</p>
                <h1 class="text-3xl font-semibold text-text-base">{{ $quote->title }}</h1>
                <p class="text-sm text-text-subtle">{{ $clientName }} · {{ $statusLabel }}</p>
            </div>
            <div class="print-hidden">
                <button type="button" class="oh-btn" onclick="window.print()">Print</button>
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

        <div class="oh-card p-5 space-y-4">
            <div class="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="text-text-subtle space-y-1">
                    <div>Quote #{{ $quote->quote_number ?? $quote->id }}</div>
                    @if ($issueDate)
                        <div>Issued {{ $issueDate->format('M j, Y') }}</div>
                    @endif
                    @if ($quote->expires_at)
                        <div>Expires {{ $quote->expires_at->format('M j, Y') }}</div>
                    @endif
                </div>
                <div class="text-text-base font-semibold">
                    Total: ${{ number_format($quote->total, 2) }}
                </div>
            </div>

            <div class="border-t border-border-default/60 pt-3 space-y-2">
                @foreach ($quote->items as $item)
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <div class="text-text-base">{{ $item->description }}</div>
                            <div class="text-xs text-text-subtle">
                                Qty {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} · ${{ number_format($item->unit_price, 2) }}
                            </div>
                        </div>
                        <div class="text-text-base font-semibold">${{ number_format($item->line_total, 2) }}</div>
                    </div>
                @endforeach
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
        </div>

        @if ($archived)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This quote has been archived. Please contact your provider for an updated quote.
            </div>
        @elseif ($expired)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This quote has expired. Please contact your provider for an updated quote.
            </div>
        @elseif ($quote->status === 'accepted' || $quote->acceptance)
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                Accepted by {{ $quote->acceptance?->signer_name ?? 'customer' }} on
                {{ optional($quote->acceptance?->accepted_at)->format('M j, Y g:i A') }}.
            </div>
        @else
            <div class="oh-card p-5 space-y-4 print-hidden">
                <div class="text-sm font-semibold text-text-base">Accept this quote</div>
                <form method="POST" action="{{ route('public.trade-quotes.accept', ['token' => request()->route('token')]) }}" class="space-y-3">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="signer_name">Your name</label>
                        <input id="signer_name" name="signer_name" class="oh-input h-10" required value="{{ old('signer_name') }}">
                        @error('signer_name')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="signer_email">Email (optional)</label>
                        <input id="signer_email" name="signer_email" class="oh-input h-10" type="email" value="{{ old('signer_email') }}">
                        @error('signer_email')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="signature">Signature</label>
                        <input id="signature" name="signature" class="oh-input h-10" required placeholder="Type your name" value="{{ old('signature') }}">
                        @error('signature')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button class="oh-btn oh-btn--primary w-full" type="submit"
                        style="background: {{ $brandColor }}; border-color: {{ $brandColor }};">
                        Accept quote
                    </button>
                </form>
            </div>
        @endif

        <div class="text-xs text-text-subtle text-center">
            {{ $brandName }}
            @if ($supportEmail)
                · {{ $supportEmail }}
            @endif
        </div>
    </div>
@endsection
