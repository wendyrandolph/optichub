@extends('layouts.app')
@section('title', 'API Keys')

@section('content')
    @php
        use App\Models\Tenant;

        $routeTenant = request()->route('tenant');
        $tenantId = match (true) {
            $routeTenant instanceof Tenant => $routeTenant->getKey(),
            is_numeric($routeTenant) => (int) $routeTenant,
            default => auth()->user()->tenant_id ?? null,
        };

        $key = $keys->first();
        $hasKey = (bool) $key;
        $keySuffix = $hasKey ? substr((string) ($key->key ?? ''), -4) : null;
        $createdAt = $hasKey ? optional($key->created_at)->format('M j, Y g:i A') : null;
        $lastUsed = $hasKey ? optional($key->last_used_at)->diffForHumans() : null;
        $baseUrl = config('app.url');
        $plainKey = $newPlainKey ?? null; // only available right after generation
        $canReveal = (bool) $plainKey;
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">API Keys</h1>
                    <p class="text-sm text-text-subtle mt-1">API keys are for integrations and webhooks—most users don’t need them.</p>
                    <p class="text-xs text-text-subtle mt-1">Safety note: Treat this like a password. Anyone with this key can send data into your workspace.</p>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('flash_success'))
            <div class="oh-card border border-emerald-200 bg-emerald-50 text-emerald-800 p-3 text-sm">
                {{ session('flash_success') }}
            </div>
        @endif

        {{-- New key banner (only immediately after generation) --}}
        @if ($plainKey)
            <div class="oh-card border border-brand-primary/40 bg-surface-muted/60 rounded-2xl p-4 md:p-5 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">Your new API key</h2>
                    <p class="text-sm text-text-subtle">Shown once. Copy and store it somewhere safe.</p>
                    <div class="font-mono text-sm text-text-base mt-2 break-all" id="newPlainKey" data-key="{{ $plainKey }}">
                        {{ $plainKey }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="oh-icon-btn" id="copyNewKeyBtn" title="Copy key" aria-label="Copy key">
                        <i class="fa-solid fa-copy text-[12px]"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- Card 1: Status --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 flex flex-col gap-4">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <h2 class="text-sm font-semibold text-text-base">API Key Status</h2>
                    <div class="flex items-center gap-2 text-sm">
                        @if ($hasKey)
                            <span class="oh-pill oh-pill--success">Active</span>
                            @if ($createdAt)
                                <span class="text-text-subtle">Created {{ $createdAt }}</span>
                            @endif
                        @else
                            <span class="oh-pill">Not generated</span>
                        @endif
                    </div>
                    @if ($lastUsed)
                        <p class="text-xs text-text-subtle">Last used {{ $lastUsed }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($hasKey)
                        <button type="button" class="oh-btn" id="revealKeyBtn" @if(!$canReveal) disabled title="Generate a new key to view it." @endif>Reveal</button>
                        <button type="button" class="oh-icon-btn" id="copyKeyBtn" title="Copy key" aria-label="Copy key" @if(!$canReveal) disabled @endif>
                            <i class="fa-solid fa-copy text-[12px]"></i>
                        </button>
                        <button type="button" class="oh-btn oh-btn--danger" id="revokeKeyBtn">Revoke</button>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <div class="font-mono text-sm text-text-base truncate" id="maskedKey">
                    @if ($hasKey)
                        @if ($canReveal)
                            ••••••••••••••••••••••{{ $keySuffix ? ' ' . $keySuffix : '' }}
                        @else
                            Key stored securely. Generate a new key to view it again.
                        @endif
                    @else
                        No key generated yet.
                    @endif
                </div>
                @if ($hasKey && $canReveal)
                    <div class="font-mono text-sm text-text-base truncate hidden" id="revealedKey" data-key="{{ $plainKey }}">
                        {{ $plainKey }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Card 2: Generate --}}
        @if (! $hasKey)
            <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Generate new key</h2>
                        <p class="text-sm text-text-subtle">Shown once. Copy and store it somewhere safe.</p>
                    </div>
                    <form method="POST" action="{{ route('tenant.settings.api.generate', ['tenant' => $tenantId]) }}">
                        @csrf
                        <button class="oh-btn oh-btn--primary">
                            <i class="fa-solid fa-key mr-2 text-[12px]"></i> Generate API Key
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Card 3: How to use --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-text-base">How to use</h2>
                    <p class="text-sm text-text-subtle">Use server-side code; do not expose keys in front-end scripts.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="oh-btn oh-btn--ghost tab-btn" data-tab="curl">cURL</button>
                    <button type="button" class="oh-btn tab-btn" data-tab="fetch">JavaScript (fetch)</button>
                </div>
            </div>

            <div class="space-y-2">
                <div class="text-xs text-text-subtle">Base URL</div>
                <div class="oh-card border border-border-default/60 bg-surface-muted/40 p-3 text-sm font-mono text-text-base">
                    {{ $baseUrl }}/api/v1
                </div>
            </div>

            <div id="code-curl" class="code-block space-y-2">
<pre class="oh-card border border-border-default/60 bg-surface-muted/40 p-3 text-sm font-mono overflow-x-auto rounded-lg">curl -X POST "{{ $baseUrl }}/api/v1/leads" \
-H "Content-Type: application/json" \
-H "X-Api-Key: YOUR_KEY" \
-d '{"first_name":"Jane","email":"jane@example.com"}'</pre>
            </div>

            <div id="code-fetch" class="code-block space-y-2 hidden">
<pre class="oh-card border border-border-default/60 bg-surface-muted/40 p-3 text-sm font-mono overflow-x-auto rounded-lg">fetch("{{ $baseUrl }}/api/v1/leads", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "X-Api-Key": "YOUR_KEY"
  },
  body: JSON.stringify({ first_name: "Jane", email: "jane@example.com" })
});</pre>
            </div>

            <p class="text-sm text-text-subtle">Include header: <code class="font-mono bg-surface-muted/60 px-2 py-1 rounded">X-Api-Key: YOUR_KEY</code></p>
        </div>

        {{-- Card 4: Security guidelines --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-5 space-y-3">
            <h2 class="text-sm font-semibold text-text-base">Security guidelines</h2>
            <div class="space-y-2 text-sm text-text-subtle">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                    <span>Don’t commit keys to source control or expose them in front-end code.</span>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                    <span>Rotate keys if you suspect exposure.</span>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                    <span>Limit who can view or regenerate keys.</span>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-check text-emerald-500 mt-0.5"></i>
                    <span>Use HTTPS for all API requests.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Revoke modal --}}
    <div id="revokeModal" class="fixed inset-0 z-50 hidden">
        <button type="button" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" data-close aria-label="Close"></button>
        <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 overflow-y-auto">
            <div class="oh-card w-full max-w-md border border-border-default shadow-card relative">
                <div class="flex items-center justify-between p-4 border-b border-border-default/70">
                    <h3 class="text-lg font-semibold text-text-base">Revoke API key</h3>
                    <button type="button" class="oh-icon-btn" data-close aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="p-4 space-y-3 text-sm text-text-subtle">
                    <p>Revoking invalidates existing integrations immediately.</p>
                    <p>You can generate a new key afterward.</p>
                </div>
                <div class="flex items-center justify-end gap-2 p-4 border-t border-border-default/60">
                    <button type="button" class="oh-btn" data-close>Cancel</button>
                    @if ($hasKey)
                        <form method="POST" action="{{ route('tenant.settings.api.revoke', ['tenant' => $tenantId, 'keyId' => $key->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--danger">Revoke</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const revealBtn = document.getElementById('revealKeyBtn');
            const copyBtn = document.getElementById('copyKeyBtn');
            const masked = document.getElementById('maskedKey');
            const revealed = document.getElementById('revealedKey');
            const revokeBtn = document.getElementById('revokeKeyBtn');
            const revokeModal = document.getElementById('revokeModal');
            const tabBtns = document.querySelectorAll('.tab-btn');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabBtns.forEach(b => b.classList.remove('oh-btn--primary'));
                    btn.classList.add('oh-btn--primary');
                    const target = btn.dataset.tab;
                    document.querySelectorAll('.code-block').forEach(el => el.classList.add('hidden'));
                    document.getElementById('code-' + target)?.classList.remove('hidden');
                });
            });

            revealBtn?.addEventListener('click', () => {
                if (!revealed) return;
                const isHidden = revealed.classList.contains('hidden');
                revealed.classList.toggle('hidden');
                masked?.classList.toggle('hidden');
                revealBtn.textContent = isHidden ? 'Hide' : 'Reveal';
            });

            copyBtn?.addEventListener('click', () => {
                const key = revealed?.dataset.key;
                if (!key) return;
                navigator.clipboard.writeText(key).then(() => {
                    copyBtn.classList.add('ring-2', 'ring-emerald-400');
                    setTimeout(() => copyBtn.classList.remove('ring-2', 'ring-emerald-400'), 800);
                });
            });

            revokeBtn?.addEventListener('click', () => revokeModal?.classList.remove('hidden'));
            revokeModal?.querySelectorAll('[data-close]')?.forEach(el => el.addEventListener('click', () => revokeModal.classList.add('hidden')));

            const copyNew = document.getElementById('copyNewKeyBtn');
            const newKeyEl = document.getElementById('newPlainKey');
            copyNew?.addEventListener('click', () => {
                const key = newKeyEl?.dataset.key;
                if (!key) return;
                navigator.clipboard.writeText(key).then(() => {
                    copyNew.classList.add('ring-2', 'ring-emerald-400');
                    setTimeout(() => copyNew.classList.remove('ring-2', 'ring-emerald-400'), 800);
                });
            });
        });
    </script>
@endpush
