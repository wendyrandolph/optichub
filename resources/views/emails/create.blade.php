@extends('layouts.app')

@section('title', 'Compose Email')

@section('content')
    @php
        // Resolve tenant id from route or model
        $t = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $t instanceof \App\Models\Tenant ? $t->getKey() : (int) $t;

        // defaults
        $sources = [
            'client' => $clients ?? collect(),
            'lead' => $leads ?? collect(),
        ];
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back / Title --}}
        <div class="flex items-start justify-between mb-6">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Emails</p>
                <h1 class="text-2xl font-semibold text-text-base">Compose Email</h1>
            </div>
            <a href="{{ route('tenant.emails.index', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--secondary">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                Back to Email Log
            </a>
        </div>

        {{-- Flash / Errors --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 text-green-800 border border-green-200 p-3">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 text-red-800 border border-red-200 p-3">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.emails.store', ['tenant' => $tenantId]) }}"
            class="oh-card border border-border-default/60 bg-surface-card/70 p-5 space-y-5">
            @csrf

            {{-- Recipient block --}}
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label for="related_type" class="block text-sm font-medium text-text-base">Recipient Type</label>
                    <select id="related_type" name="related_type" class="oh-select mt-1 w-full h-10" required>
                        <option value="" @selected(old('related_type') === '')>Select…</option>
                        <option value="client" @selected(old('related_type') === 'client')>Client</option>
                        <option value="lead" @selected(old('related_type') === 'lead')>Lead</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="related_id" class="block text-sm font-medium text-text-base">Recipient</label>
                    <select id="related_id" name="related_id" class="oh-select mt-1 w-full h-10" required>
                        <option value="">Choose a recipient…</option>
                        {{-- We render both groups then JS filters by type --}}
                        <optgroup label="Clients" data-type="client">
                            @foreach ($sources['client'] as $c)
                                @php
                                    $cid = $c->id ?? $c['id'];
                                    $first = $c->firstName ?? ($c['firstName'] ?? $c->first_name ?? ($c['first_name'] ?? null));
                                    $last = $c->lastName ?? ($c['lastName'] ?? $c->last_name ?? ($c['last_name'] ?? null));
                                    $full = trim(($first ?? '') . ' ' . ($last ?? ''));
                                    $label =
                                        $full !== ''
                                            ? $full
                                            : ($c->name ??
                                                ($c['name'] ?? ($c->client_name ?? ($c['client_name'] ?? 'Client'))));
                                    $email = $c->email ?? ($c['email'] ?? '');
                                @endphp
                                <option value="{{ $cid }}" data-type="client" data-email="{{ $email }}"
                                    @selected(old('related_type') === 'client' && old('related_id') == $cid)>
                                    {{ $label }} @if ($email)
                                        — {{ $email }}
                                    @endif
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Leads" data-type="lead">
                            @foreach ($sources['lead'] as $l)
                                @php
                                    $lid = $l->id ?? $l['id'];
                                    $first = $l->first_name ?? ($l['first_name'] ?? $l->firstName ?? ($l['firstName'] ?? null));
                                    $last = $l->last_name ?? ($l['last_name'] ?? $l->lastName ?? ($l['lastName'] ?? null));
                                    $full = trim(($first ?? '') . ' ' . ($last ?? ''));
                                    $label =
                                        $full !== ''
                                            ? $full
                                            : ($l->name ?? ($l['name'] ?? 'Lead'));
                                    $email = $l->email ?? ($l['email'] ?? '');
                                @endphp
                                <option value="{{ $lid }}" data-type="lead" data-email="{{ $email }}"
                                    @selected(old('related_type') === 'lead' && old('related_id') == $lid)>
                                    {{ $label }} @if ($email)
                                        — {{ $email }}
                                    @endif
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <p class="mt-1 text-xs text-text-subtle">We’ll auto-fill the “To” address when possible.</p>
                </div>
            </div>

            {{-- To Email (can be overridden) --}}
            <div>
                <label for="recipient_email" class="block text-sm font-medium text-text-base">To</label>
                <input id="recipient_email" name="recipient_email" type="email" required
                    value="{{ old('recipient_email') }}"
                    class="oh-input mt-1 w-full h-10"
                    placeholder="person@example.com">
            </div>

            {{-- Subject --}}
            <div>
                <label for="subject" class="block text-sm font-medium text-text-base">Subject</label>
                <input id="subject" name="subject" type="text" required value="{{ old('subject') }}"
                    class="oh-input mt-1 w-full h-10"
                    placeholder="Subject">
            </div>

            {{-- Body --}}
            <div>
                <label for="body" class="block text-sm font-medium text-text-base">Message</label>
                <textarea id="body" name="body" rows="8"
                    class="oh-textarea mt-1 w-full"
                    placeholder="Write your message…">{{ old('body') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('tenant.emails.index', ['tenant' => $tenantId]) }}"
                    class="oh-btn oh-btn--secondary">
                    Cancel
                </a>
                <button type="submit" class="oh-btn oh-btn--primary">
                    Send & Log
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const typeSel = document.getElementById('related_type');
            const idSel = document.getElementById('related_id');
            const emailInp = document.getElementById('recipient_email');

            function filterRecipients() {
                const t = typeSel.value;
                [...idSel.options].forEach(opt => {
                    if (!opt.value) return; // placeholder
                    const ok = (opt.dataset.type === t);
                    opt.hidden = !ok;
                });

                // Reset selection if wrong type
                const current = idSel.selectedOptions[0];
                if (current && current.hidden) idSel.value = '';

                // Auto-pick the first visible option on type change
                if (!idSel.value) {
                    const firstVisible = [...idSel.options].find(o => !o.hidden && o.value);
                    if (firstVisible) {
                        idSel.value = firstVisible.value;
                        setEmailFromOption(firstVisible);
                    }
                }
            }

            function setEmailFromOption(opt) {
                const em = opt?.dataset?.email || '';
                if (em) emailInp.value = em;
            }

            typeSel?.addEventListener('change', filterRecipients);
            idSel?.addEventListener('change', () => setEmailFromOption(idSel.selectedOptions[0]));

            // initialize on load (respect old() values)
            filterRecipients();
            if (!emailInp.value) setEmailFromOption(idSel.selectedOptions[0]);
        })();
    </script>
@endpush
