@php
    $tenantId = $tenant->id ?? null;
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-2">
        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
        <div>
            <h1 class="text-2xl font-semibold text-text-base">Payments</h1>
            <p class="text-sm text-text-subtle mt-1">
                Stripe Connect is recommended for automatic payment recording.
            </p>
        </div>
    </div>

    <div class="oh-card border border-border-default/60 rounded-2xl p-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-text-base">Connect Stripe (recommended)</div>
                <div class="text-sm text-text-subtle">
                    Accept card payments and record them automatically in Renlo.
                </div>
            </div>
            <button type="button" class="oh-btn" disabled>Connect Stripe</button>
        </div>
        <p class="text-xs text-text-subtle">
            Stripe Connect setup is coming soon.
        </p>
    </div>

    <div class="oh-card border border-border-default/60 rounded-2xl p-5 space-y-4">
        <div>
            <div class="text-sm font-semibold text-text-base">Manual payment methods</div>
            <div class="text-sm text-text-subtle">
                If you use another processor, add a payment link or instructions for clients.
            </div>
        </div>

        <form method="POST" action="{{ route('tenant.settings.payments.manual.store', ['tenant' => $tenantId]) }}"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-text-base" for="label">Label</label>
                <input id="label" name="label" class="oh-input h-10" required placeholder="Pay by card (Square)">
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-text-base" for="external_url">Payment link</label>
                <input id="external_url" name="external_url" class="oh-input h-10" placeholder="https://">
            </div>
            <div class="space-y-1.5 md:col-span-2">
                <label class="text-sm font-medium text-text-base" for="instructions">Instructions</label>
                <textarea id="instructions" name="instructions" rows="3" class="oh-input min-h-[90px]"
                    placeholder="Call us at 555-1234 or send a check to..."></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm md:col-span-2">
                <input type="checkbox" name="is_enabled" value="1" checked
                    class="rounded border-border-default text-brand-primary">
                <span class="text-text-base">Enabled</span>
            </label>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="oh-btn oh-btn--primary">Add payment method</button>
            </div>
        </form>

        <div class="space-y-3">
            @forelse ($manualMethods as $method)
                <div class="rounded-xl border border-border-default/60 p-4">
                    <form method="POST"
                        action="{{ route('tenant.settings.payments.manual.update', ['tenant' => $tenantId, 'integration' => $method->id]) }}"
                        class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        @method('PATCH')
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-text-base" for="label-{{ $method->id }}">Label</label>
                            <input id="label-{{ $method->id }}" name="label" class="oh-input h-10"
                                value="{{ $method->label }}" required>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-text-base" for="external_url-{{ $method->id }}">Payment link</label>
                            <input id="external_url-{{ $method->id }}" name="external_url" class="oh-input h-10"
                                value="{{ $method->external_url }}">
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label class="text-sm font-medium text-text-base" for="instructions-{{ $method->id }}">Instructions</label>
                            <textarea id="instructions-{{ $method->id }}" name="instructions" rows="3"
                                class="oh-input min-h-[90px]">{{ $method->instructions }}</textarea>
                        </div>
                        <label class="flex items-center gap-2 text-sm md:col-span-2">
                            <input type="checkbox" name="is_enabled" value="1" @checked($method->is_enabled)
                                class="rounded border-border-default text-brand-primary">
                            <span class="text-text-base">Enabled</span>
                        </label>
                        <div class="md:col-span-2 flex items-center justify-between gap-3">
                            <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                        </div>
                    </form>
                    <form method="POST"
                        action="{{ route('tenant.settings.payments.manual.destroy', ['tenant' => $tenantId, 'integration' => $method->id]) }}"
                        class="mt-3 flex justify-end">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-btn oh-btn--danger"
                            onclick="return confirm('Remove this payment method?')">
                            Remove
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-sm text-text-subtle">No manual methods yet.</div>
            @endforelse
        </div>
    </div>
</div>
