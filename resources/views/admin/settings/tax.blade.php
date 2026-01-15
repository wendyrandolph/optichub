{{-- resources/views/admin/settings/tax.blade.php --}}
@extends('layouts.app')
@section('title', 'Tax settings')

@section('content')
    @php
        $tenantId = $tenant->id ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Tax settings</h1>
                    <p class="text-sm text-text-subtle mt-1">Configure how invoices handle tax for your workspace.</p>
                </div>
            </div>
            <div class="oh-card border border-amber-200 bg-amber-50 text-amber-800 p-3 text-sm">
                Renlo doesn’t provide tax or legal advice. Please confirm requirements with your accountant.
            </div>
        </div>

        {{-- Flash / Errors --}}
        @if (session('status'))
            <div class="oh-card border border-emerald-200 bg-emerald-50 text-emerald-800 p-3 text-sm">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-800 p-3 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- Behavior card --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-4">
            <div class="space-y-1">
                <h2 class="text-sm font-semibold text-text-base">Tax behavior</h2>
                <p class="text-sm text-text-subtle">Turn tax on/off and control whether prices include tax.</p>
            </div>

            @if (!$tenant->tax_enabled)
                <div class="oh-card border border-amber-200 bg-amber-50 text-amber-800 p-3 text-xs">
                    Tax is currently disabled. Enable it if you must collect tax.
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.settings.tax.update', ['tenant' => $tenant->id]) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="tax_enabled" value="1" @checked(old('tax_enabled', $tenant->tax_enabled))
                        class="mt-1 h-4 w-4 rounded border-border-default text-brand-primary">
                    <span>
                        <span class="text-text-base font-medium">Enable tax on invoices</span>
                        <p class="text-text-subtle">When disabled, invoices will not calculate or display tax amounts.</p>
                    </span>
                </label>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="tax_inclusive" value="1" @checked(old('tax_inclusive', $tenant->tax_inclusive))
                        class="mt-1 h-4 w-4 rounded border-border-default text-brand-primary">
                    <span>
                        <span class="text-text-base font-medium">Line item prices include tax</span>
                        <p class="text-text-subtle">Turn on if prices already include tax and you only need a breakdown.</p>
                    </span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Default currency</span>
                        <div class="flex items-center gap-2">
                            <input type="text" name="default_currency"
                                value="{{ old('default_currency', $tenant->default_currency ?? 'USD') }}"
                                class="oh-input h-10 w-28 uppercase">
                            <span class="text-xs text-text-subtle">3-letter code, e.g. USD, CAD, EUR.</span>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border-default/60">
                    <button type="submit" class="oh-btn oh-btn--primary">Save tax settings</button>
                </div>
            </form>
        </div>

        {{-- Rates card --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-4">
            <div class="space-y-1">
                <h2 class="text-sm font-semibold text-text-base">Tax rates</h2>
                <p class="text-sm text-text-subtle">Define rates to apply to invoices (e.g., state or local tax).</p>
            </div>

            <div class="space-y-3">
                @forelse($taxRates as $rate)
                    <div class="oh-card border border-border-default/60 bg-surface-muted/40 p-3 space-y-2">
                        <form method="POST"
                            action="{{ route('tenant.settings.rates.update', ['tenant' => $tenant->id, 'taxRate' => $rate]) }}"
                            class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                            @csrf
                            @method('PUT')

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Name</span>
                                <input type="text" name="name" value="{{ old('name', $rate->name) }}" class="oh-input h-10">
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Rate (decimal)</span>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="rate" value="{{ old('rate', $rate->rate) }}" step="0.0001" min="0" max="1"
                                        class="oh-input h-10 text-right">
                                    <span class="text-xs text-text-subtle">= {{ number_format((float) $rate->rate * 100, 2) }}%</span>
                                </div>
                            </label>

                            <div class="space-y-2">
                                <span class="text-xs font-medium text-text-subtle">Options</span>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-text-base">
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="applies_by_default" value="1"
                                            @checked(old('applies_by_default', $rate->applies_by_default))
                                            class="h-3 w-3 rounded border-border-default text-brand-primary">
                                        Applies by default
                                    </label>
                                    <label class="inline-flex items-center gap-1">
                                        <input type="checkbox" name="active" value="1" @checked(old('active', $rate->active))
                                            class="h-3 w-3 rounded border-border-default text-brand-primary">
                                        Active
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <button type="submit" class="oh-btn">Save</button>
                            </div>
                        </form>

                        <form method="POST"
                            action="{{ route('tenant.settings.rates.destroy', ['tenant' => $tenant->id, 'taxRate' => $rate]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="oh-btn oh-btn--danger text-xs">Delete rate</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-text-subtle">No tax rates defined yet. Add your first one below.</p>
                @endforelse
            </div>

            <div class="border-t border-border-default/60 pt-4 space-y-3">
                <h3 class="text-sm font-semibold text-text-base">Add tax rate</h3>
                <form method="POST" action="{{ route('tenant.settings.rates.store', ['tenant' => $tenant->id]) }}"
                    class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    @csrf

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Name</span>
                        <input type="text" name="name" placeholder="e.g. State tax" class="oh-input h-10">
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Rate (decimal)</span>
                        <div class="flex items-center gap-2">
                            <input type="number" name="rate" step="0.0001" min="0" max="1" placeholder="0.0600"
                                class="oh-input h-10 text-right">
                            <span class="text-xs text-text-subtle">0.0600 = 6%</span>
                        </div>
                    </label>

                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-1 text-xs text-text-base">
                            <input type="checkbox" name="applies_by_default" value="1"
                                class="h-3 w-3 rounded border-border-default text-brand-primary">
                            Applies by default
                        </label>
                        <div class="ml-auto">
                            <button type="submit" class="oh-btn oh-btn--primary">Add tax rate</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
