@php
    use App\Services\FeatureGate;

    $tenantId = $tenant->id ?? null;
    $featureGate = app(FeatureGate::class);
    $actor = auth('admin')->user() ?? auth()->user();
    $isProviderAdmin = $actor?->isProviderAdmin() ?? false;
    $canPayments = $tenant ? $featureGate->allows($tenant, FeatureGate::PAYMENTS_FRAMEWORK, $actor) : false;
    $canExternal = $tenant ? $featureGate->allows($tenant, FeatureGate::PAYMENTS_EXTERNAL_LINK, $actor) : false;
    $canStripe = $tenant ? $featureGate->allows($tenant, FeatureGate::PAYMENTS_STRIPE, $actor) : false;
    $canWave = $tenant ? $featureGate->allows($tenant, FeatureGate::PAYMENTS_WAVE, $actor) : false;
    $upgradeHref = $tenantId ? route('tenant.settings.billing-upgrade', ['tenant' => $tenantId]) : '#';
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col gap-2">
        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold text-text-base">Payments</h1>
                @if ($isProviderAdmin)
                    <span class="oh-pill oh-pill--info">Provider mode</span>
                @endif
            </div>
            <p class="text-sm text-text-subtle mt-1">
                Stripe Connect is recommended for automatic payment recording.
            </p>
        </div>
    </div>

    @if (! $canPayments && ! $isProviderAdmin)
        <div class="oh-card border border-border-default/60 rounded-2xl p-5 space-y-3">
            <div class="text-sm font-semibold text-text-base">Payments are not enabled for this plan.</div>
            <p class="text-sm text-text-subtle">
                Upgrade your plan to configure payment links and provider connections.
            </p>
            <div>
                <a href="{{ $upgradeHref }}" class="oh-btn oh-btn--secondary">View plans</a>
            </div>
        </div>
    @endif

    <div class="oh-card border border-border-default/60 rounded-2xl p-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-text-base">Connect Stripe (recommended)</div>
                <div class="text-sm text-text-subtle">
                    Accept card payments and record them automatically in Renlo.
                </div>
                @if ($stripeAccount)
                    <div class="mt-2 text-xs text-text-subtle">
                        Status: <span class="font-semibold text-text-base">{{ ucfirst($stripeAccount->status) }}</span>
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($stripeAccount && $stripeAccount->status === 'active')
                    <form method="POST" action="{{ route('tenant.settings.payments.stripe.disconnect', ['tenant' => $tenantId]) }}">
                        @csrf
                        <button type="submit" class="oh-btn oh-btn--secondary">
                            Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('tenant.settings.payments.stripe.connect', ['tenant' => $tenantId]) }}"
                        class="oh-btn {{ ! $canStripe && ! $isProviderAdmin ? 'pointer-events-none opacity-60' : '' }}">
                        Connect Stripe
                    </a>
                @endif
            </div>
        </div>
        <div class="flex items-center justify-between gap-3 text-xs text-text-subtle">
            <span>
                @if ($stripeAccount && $stripeAccount->status === 'active')
                    Stripe is connected. Clients can pay invoices online.
                @else
                    Stripe Connect setup will guide you through onboarding.
                @endif
            </span>
            @if (! $canStripe && ! $isProviderAdmin)
                <a href="{{ $upgradeHref }}" class="oh-btn">Upgrade for Stripe</a>
            @endif
        </div>
        @if ($stripeAccount)
            <form method="POST" action="{{ route('tenant.settings.payments.stripe.status', ['tenant' => $tenantId]) }}"
                class="flex items-center justify-between gap-3 text-xs text-text-subtle pt-2 border-t border-border-default/60">
                @csrf
                <div>
                    <div class="text-sm font-medium text-text-base">Enable payments on invoices</div>
                    <div class="text-xs text-text-subtle">Toggle whether clients can pay invoices online.</div>
                </div>
                <input type="hidden" name="status" value="{{ $stripeAccount->status === 'active' ? 'disabled' : 'active' }}">
                <button type="submit" class="oh-btn">
                    {{ $stripeAccount->status === 'active' ? 'Disable' : 'Enable' }}
                </button>
            </form>
        @endif
    </div>

    <div class="oh-card border border-border-default/60 rounded-2xl p-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-text-base">Connect Wave</div>
                <div class="text-sm text-text-subtle">
                    Send invoices through Wave while keeping records in Renlo.
                </div>
                @if ($waveAccount)
                    <div class="mt-2 text-xs text-text-subtle">
                        Status: <span class="font-semibold text-text-base">{{ ucfirst($waveAccount->status) }}</span>
                    </div>
                @endif
            </div>
            <a href="{{ route('tenant.settings.payments.wave.connect', ['tenant' => $tenantId]) }}"
                class="oh-btn {{ ! $canWave && ! $isProviderAdmin ? 'pointer-events-none opacity-60' : '' }}">
                Connect Wave
            </a>
        </div>
        <div class="flex items-center justify-between gap-3 text-xs text-text-subtle">
            <span>Wave setup is coming soon.</span>
            @if (! $canWave && ! $isProviderAdmin)
                <a href="{{ $upgradeHref }}" class="oh-btn">Upgrade for Wave</a>
            @endif
        </div>
    </div>

    <div class="oh-card border border-border-default/60 rounded-2xl p-5 space-y-4">
        <div>
            <div class="text-sm font-semibold text-text-base">Manual payment methods</div>
            <div class="text-sm text-text-subtle">
                If you use another processor, add a payment link or instructions for clients.
            </div>
        </div>

        @if (! $canExternal && ! $isProviderAdmin)
            <div class="text-xs text-text-subtle">
                Upgrade to add external payment links and manual instructions.
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.settings.payments.manual.store', ['tenant' => $tenantId]) }}"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-text-base" for="label">Label</label>
                <input id="label" name="label" class="oh-input h-10" required placeholder="Pay by card (Square)"
                    @disabled(! $canExternal && ! $isProviderAdmin)>
            </div>
            <div class="space-y-1.5">
                <label class="text-sm font-medium text-text-base" for="external_url">Payment link</label>
                <input id="external_url" name="external_url" class="oh-input h-10" placeholder="https://"
                    @disabled(! $canExternal && ! $isProviderAdmin)>
            </div>
            <div class="space-y-1.5 md:col-span-2">
                <label class="text-sm font-medium text-text-base" for="instructions">Instructions</label>
                <textarea id="instructions" name="instructions" rows="3" class="oh-input min-h-[90px]"
                    placeholder="Call us at 555-1234 or send a check to..."
                    @disabled(! $canExternal && ! $isProviderAdmin)></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm md:col-span-2">
                <input type="checkbox" name="is_enabled" value="1" checked
                    class="rounded border-border-default text-brand-primary"
                    @disabled(! $canExternal && ! $isProviderAdmin)>
                <span class="text-text-base">Enabled</span>
            </label>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="oh-btn oh-btn--primary"
                    @disabled(! $canExternal && ! $isProviderAdmin)>
                    Add payment method
                </button>
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
                                value="{{ $method->label }}" required @disabled(! $canExternal && ! $isProviderAdmin)>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-text-base" for="external_url-{{ $method->id }}">Payment link</label>
                            <input id="external_url-{{ $method->id }}" name="external_url" class="oh-input h-10"
                                value="{{ $method->external_url }}" @disabled(! $canExternal && ! $isProviderAdmin)>
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label class="text-sm font-medium text-text-base" for="instructions-{{ $method->id }}">Instructions</label>
                            <textarea id="instructions-{{ $method->id }}" name="instructions" rows="3"
                                class="oh-input min-h-[90px]"
                                @disabled(! $canExternal && ! $isProviderAdmin)>{{ $method->instructions }}</textarea>
                        </div>
                        <label class="flex items-center gap-2 text-sm md:col-span-2">
                            <input type="checkbox" name="is_enabled" value="1" @checked($method->is_enabled)
                                class="rounded border-border-default text-brand-primary"
                                @disabled(! $canExternal && ! $isProviderAdmin)>
                            <span class="text-text-base">Enabled</span>
                        </label>
                        <div class="md:col-span-2 flex items-center justify-between gap-3">
                            <button type="submit" class="oh-btn oh-btn--primary"
                                @disabled(! $canExternal && ! $isProviderAdmin)>
                                Save
                            </button>
                        </div>
                    </form>
                    <form method="POST"
                        action="{{ route('tenant.settings.payments.manual.destroy', ['tenant' => $tenantId, 'integration' => $method->id]) }}"
                        class="mt-3 flex justify-end">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-btn oh-btn--danger"
                            onclick="return confirm('Remove this payment method?')"
                            @disabled(! $canExternal && ! $isProviderAdmin)>
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
