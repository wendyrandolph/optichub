@extends('layouts.trades')

@section('title', 'Trades Settings — Profile')

@section('trades-content')
    @php
        $primarySeed = $tenant?->primary_color ?: '#1c2e70';
        $secondarySeed = $tenant?->secondary_color ?: '#8faf9a';
        $accentSeed = $tenant?->accent_color ?: '#9a8fbf';

        $primaryColorVal = old('primary_color', $primarySeed) ?: $primarySeed;
        $secondaryColorVal = old('secondary_color', $secondarySeed) ?: $secondarySeed;
        $accentColorVal = old('accent_color', $accentSeed) ?: $accentSeed;
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <h1 class="text-2xl font-semibold text-text-base">Business Profile</h1>
            <p class="text-sm text-text-subtle mt-1">Branding and business identity for your trades workspace.</p>
        </div>

        <form method="POST" action="{{ route('tenant.trades.settings.profile.update', ['tenant' => $tenant->id]) }}"
            enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="oh-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-text-base">Business identity</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="name">Business name</label>
                        <input id="name" name="name" class="oh-input h-10" required
                            value="{{ old('name', $tenant->name) }}">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="website">Website</label>
                        <input id="website" name="website" class="oh-input h-10"
                            value="{{ old('website', $tenant->website) }}">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="phone">Phone</label>
                        <input id="phone" name="phone" class="oh-input h-10"
                            value="{{ old('phone', $tenant->phone) }}">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="support_email">Support email</label>
                        <input id="support_email" name="support_email" class="oh-input h-10" type="email"
                            value="{{ old('support_email', $tenant->support_email) }}">
                    </div>
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="brand_tagline">Brand tagline</label>
                        <input id="brand_tagline" name="brand_tagline" class="oh-input h-10"
                            value="{{ old('brand_tagline', $tenant->brand_tagline) }}">
                    </div>
                </div>
            </div>

            <div class="oh-card p-6 space-y-4">
                <h2 class="text-base font-semibold text-text-base">Branding</h2>
                <p class="text-sm text-text-subtle mt-1">Provide the hex values for your brand colors. Default colors will
                    be used if nothing is set. The logo and brand colors will be used in the invoices, and quotes that get
                    created.</p>
                <div class="grid grid-cols-1  gap-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="oh-card border border-border-default/60 p-3 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="h-8 w-8 rounded-full border border-border-default/70"
                                    style="background: {{ $primaryColorVal }}"></span>
                                <div>
                                    <div class="text-sm font-semibold text-text-base">Primary</div>
                                    <div class="text-xs text-text-subtle">{{ $primaryColorVal }}</div>
                                </div>
                            </div>
                            <input name="primary_color" value="{{ $primaryColorVal }}" class="oh-input h-10">
                            @error('primary_color')
                                <span class="text-xs text-rose-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="oh-card border border-border-default/60 p-3 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="h-8 w-8 rounded-full border border-border-default/70"
                                    style="background: {{ $secondaryColorVal }}"></span>
                                <div>
                                    <div class="text-sm font-semibold text-text-base">Secondary</div>
                                    <div class="text-xs text-text-subtle">{{ $secondaryColorVal }}</div>
                                </div>
                            </div>
                            <input name="secondary_color" value="{{ $secondaryColorVal }}" class="oh-input h-10">
                            @error('secondary_color')
                                <span class="text-xs text-rose-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="oh-card border border-border-default/60 p-3 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="h-8 w-8 rounded-full border border-border-default/70"
                                    style="background: {{ $accentColorVal }}"></span>
                                <div>
                                    <div class="text-sm font-semibold text-text-base">Accent</div>
                                    <div class="text-xs text-text-subtle">{{ $accentColorVal }}</div>
                                </div>
                            </div>
                            <input name="accent_color" value="{{ $accentColorVal }}" class="oh-input h-10">
                            @error('accent_color')
                                <span class="text-xs text-rose-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm text-text-subtle">Logo</label>
                        <div class="flex items-center gap-3">
                            <input id="brandingLogo" type="file" name="logo"
                                accept=".png,.svg,image/png,image/svg+xml" class="sr-only">
                            <label for="brandingLogo"
                                class="inline-flex items-center rounded-md border border-border-default bg-surface-card px-3 py-2 text-sm font-medium text-text-base shadow-sm hover:bg-surface-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgba(var(--ui-primary),0.35)] cursor-pointer">
                                Choose file
                            </label>
                            <span id="brandingLogoFilename" class="text-sm text-text-subtle">
                                {{ $logoUrl ? 'Current logo selected' : 'No file chosen' }}
                            </span>
                        </div>
                        <div class="text-xs text-text-subtle">
                            Recommended size: 512×512px (square). Minimum: 200×200px. Formats: PNG or SVG. Max file size:
                            1MB.
                        </div>
                        @error('logo')
                            <span class="text-xs text-rose-600">{{ $message }}</span>
                        @enderror
                    </div>

                    @if (!empty($logoUrl))
                        <div class="mt-2">
                            <img src="{{ $logoUrl }}" alt="{{ $tenant->name }} logo" class="h-20 w-auto">
                        </div>
                    @endif

                </div>
                <div class="space-y-1.5 md:col-span-2">
                    <label class="text-sm font-medium text-text-base" for="invoice_footer">Invoice footer</label>
                    <textarea id="invoice_footer" name="invoice_footer" rows="3" class="oh-input min-h-[110px]">{{ old('invoice_footer', $tenant->invoice_footer) }}</textarea>
                    <p class="text-xs text-text-subtle">Optional note shown on client invoices.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <button class="oh-btn oh-btn--primary" type="submit">Save profile</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('brandingLogo');
            const label = document.getElementById('brandingLogoFilename');
            if (!input || !label) return;

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                label.textContent = file ? file.name : 'No file chosen';
            });
        });
    </script>
@endpush
