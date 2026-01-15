@extends('layouts.app')

@section('title', 'Services')

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? request()->route('tenant'));
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;
    @endphp
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('tenant.companies.show', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                    class="inline-flex items-center text-xs text-text-subtle hover:text-text-base mb-1">
                    <i class="fa-solid fa-arrow-left mr-1"></i>
                    Back to company
                </a>
                <h1 class="text-2xl font-semibold text-text-base">Services</h1>
                <p class="text-sm text-text-subtle mt-1">Manage hosting, domains, maintenance, and retainers for this company.</p>
            </div>
            <button type="button" id="openServiceModal" class="oh-btn oh-btn--primary">
                <i class="fa-solid fa-plus mr-2 text-xs"></i>
                Add Service
            </button>
        </header>

        {{-- Toolbar --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 mb-2">
            <form method="GET" action="{{ route('tenant.companies.services.index', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                class="p-4 md:p-5 flex flex-col gap-3">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex-1 md:max-w-[360px]">
                        <input name="q" value="{{ $q ?? '' }}" placeholder="Search service, provider, domain…"
                            class="w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                            border border-border-default focus:outline-none focus:ring-1 focus:ring-[rgb(var(--brand-primary)/.45)]">
                    </div>
                    <div class="flex items-center gap-2">
                        <select name="type" class="oh-input">
                            <option value="">All types</option>
                            @foreach (['domain','hosting','maintenance','retainer','other'] as $t)
                                <option value="{{ $t }}" @selected(($typeFilter ?? '') === $t)>{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                        @if (($q ?? '') !== '' || ($typeFilter ?? '') !== '' || ($timing ?? '') !== '' || ($statusFilter ?? '') !== '')
                            <a href="{{ route('tenant.companies.services.index', ['tenant' => $tenantId, 'company' => $company->id]) }}" class="oh-btn">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Filter chips --}}
        <div class="flex flex-wrap gap-2 mb-3">
            @php
                $chips = [
                    ['label' => 'All', 'status' => '', 'timing' => ''],
                    ['label' => 'Active', 'status' => 'active', 'timing' => ''],
                    ['label' => 'Inactive', 'status' => 'inactive', 'timing' => ''],
                    ['label' => 'Due soon', 'status' => '', 'timing' => 'soon'],
                    ['label' => 'Overdue', 'status' => '', 'timing' => 'overdue'],
                ];
            @endphp
            @foreach ($chips as $chip)
                @php
                    $isActive = ($statusFilter ?? '') === $chip['status'] && ($timing ?? '') === $chip['timing'];
                    $url = route('tenant.companies.services.index', [
                        'tenant' => $tenantId,
                        'company' => $company->id,
                        'status' => $chip['status'] ?: null,
                        'timing' => $chip['timing'] ?: null,
                        'q' => $q ?? null,
                        'type' => $typeFilter ?? null,
                    ]);
                @endphp
                <a href="{{ $url }}"
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border border-border-default
                    {{ $isActive ? 'bg-[rgb(var(--brand-primary)/.14)] text-text-base ring-1 ring-[rgb(var(--brand-primary)/.25)]' : 'bg-surface-card text-text-subtle hover:text-text-base' }}"
                    aria-pressed="{{ $isActive ? 'true' : 'false' }}">
                    <span>{{ $chip['label'] }}</span>
                    @if ($isActive)
                        <i class="fa-solid fa-check text-[10px] opacity-70"></i>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Summary strip --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 px-4 py-3 text-sm text-text-subtle flex flex-wrap gap-4">
            <span>Active: {{ $summary['active'] ?? 0 }}</span>
            <span>Due soon: {{ $summary['dueSoon'] ?? 0 }}</span>
            <span>Overdue: {{ $summary['overdue'] ?? 0 }}</span>
            <span>Next renewal:
                @if (!empty($summary['nextRenewal']?->renewal_date))
                    {{ optional($summary['nextRenewal']->renewal_date)->format('M j') }} ({{ $summary['nextRenewal']->name ?? '—' }})
                @else
                    —
                @endif
            </span>
        </div>

        {{-- Add/Edit Service Modal --}}
        <div id="serviceModal" class="hidden fixed inset-0 z-40 bg-black/40 backdrop-blur-sm">
            <div class="min-h-full flex items-start justify-center px-4 py-8">
                <div id="serviceModalCard" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle"
                    class="oh-card rounded-2xl border border-border-default/70 shadow-lg w-[92vw] sm:w-full max-w-2xl max-h-[90vh] overflow-hidden bg-surface-card bg-white flex flex-col sm:mt-6">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-border-default/60 shrink-0">
                        <h2 id="serviceModalTitle" class="text-lg font-semibold text-text-base">Add Service</h2>
                        <button type="button" id="closeServiceModal" class="oh-icon-btn" aria-label="Close">
                            <i class="fa-solid fa-xmark text-[14px]"></i>
                        </button>
                    </div>
                    <form id="serviceForm" method="POST"
                        action="{{ route('tenant.companies.services.store', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                        class="flex-1 flex flex-col overflow-hidden">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="serviceFormMethod">

                        <div class="flex-1 overflow-y-auto px-5 pt-4 pb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">Basics</div>
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Service name</span>
                                    <input type="text" name="name" id="serviceName"
                                        class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                </label>
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Type</span>
                                    <select name="type" id="serviceType"
                                        class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary" required>
                                        <option value="">Select</option>
                                        <option value="domain">Domain</option>
                                        <option value="hosting">Hosting</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="retainer">Retainer</option>
                                        <option value="other">Other</option>
                                    </select>
                                </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Status</span>
                            <select name="status" id="serviceStatus"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Handling</span>
                            <select name="handling" id="serviceHandling"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                <option value="">Select</option>
                                <option value="client">Client handles/pays</option>
                                <option value="agency">We handle/pay</option>
                                <option value="agency_reimburse">We pay, client reimburses</option>
                            </select>
                        </label>

                                <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">Billing</div>
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Billing cycle</span>
                                    <select name="billing_cycle" id="serviceBilling"
                                        class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                        <option value="">Select</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="annual">Annual</option>
                                <option value="one_time">One-time</option>
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Renewal date</span>
                            <input type="date" name="renewal_date" id="serviceRenewal"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Reminder lead time (days)</span>
                            <input type="number" min="0" max="365" name="reminder_days" id="serviceReminder"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary"
                                placeholder="e.g., 30">
                        </label>

                                <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">Provider</div>
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Provider</span>
                                    <input type="text" name="provider" id="serviceProvider"
                                        class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                </label>
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Provider URL</span>
                                    <input type="text" name="provider_url" id="serviceProviderUrl"
                                        class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                </label>

                                <div id="domainFields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                                    <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">Domain details</div>
                                    <label class="grid gap-1 text-sm">
                                        <span class="text-text-subtle">Domain registrar</span>
                                        <input type="text" name="registrar" id="serviceRegistrar"
                                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary"
                                            placeholder="e.g., GoDaddy, Namecheap">
                                    </label>
                                    <label class="grid gap-1 text-sm">
                                        <span class="text-text-subtle">Domain name</span>
                                        <input type="text" name="domain_name" id="serviceDomainName"
                                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary"
                                            placeholder="example.com">
                                    </label>
                                </div>

                                <div class="md:col-span-2 text-xs font-semibold uppercase tracking-wide text-text-subtle">Notes</div>
                                <label class="grid gap-1 text-sm md:col-span-2">
                                    <textarea name="notes" id="serviceNotes" rows="3"
                                        class="w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary"></textarea>
                                </label>
                            </div>
                        </div>

                    </form>
                    <div class="border-t border-border-default/60 bg-surface-card px-5 py-4 flex items-center justify-end gap-2">
                        <button type="button" id="closeServiceModal2" class="oh-btn">Cancel</button>
                        <button type="submit" form="serviceForm" class="oh-btn oh-btn--primary" id="serviceSaveBtn">Save service</button>
                    </div>
                </div>
            </div>
        </div>

        <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm">
            <div class="divide-y divide-border-default/60">
                @php
                    $serviceTypeClass = function ($type) {
                        return match (strtolower((string) $type)) {
                            'hosting' => 'oh-pill oh-pill--info',
                            'domain' => 'oh-pill oh-pill--warning',
                            'maintenance' => 'oh-pill oh-pill--success',
                            'retainer' => 'oh-pill oh-pill--muted',
                            default => 'oh-pill',
                        };
                    };
                    $serviceStatusClass = function ($status) {
                        return match (strtolower((string) $status)) {
                            'active' => 'oh-pill oh-pill--success',
                            'paused' => 'oh-pill oh-pill--warning',
                            'canceled', 'cancelled' => 'oh-pill oh-pill--danger',
                            default => 'oh-pill oh-pill--muted',
                        };
                    };
                @endphp
                @forelse ($services as $service)
                    @php
                        $usedAmount = $service->used_amount ?? 0;
                        $usedHours = $service->used_hours ?? 0;
                        $balance = null;
                        $meta = $service->meta ?? [];
                        $retainerType = $meta['retainer_type'] ?? null;
                        $retainerAmount = $meta['retainer_amount'] ?? null;
                        if ($service->type === 'retainer' && $retainerAmount !== null) {
                            if ($retainerType === 'hours') {
                                $balance = $retainerAmount - $usedHours;
                            } else {
                                $balance = $retainerAmount - ($usedAmount ?? 0);
                            }
                        }
                        $days = $service->days_until_renewal ?? null;
                        $renewDate = $service->renewal_date ? \Illuminate\Support\Carbon::parse($service->renewal_date, $tenant->timezone ?? config('app.timezone')) : null;
                        $renewText = $renewDate
                            ? 'Renews ' . $renewDate->format('M j, Y') . ' · ' . $renewDate->diffForHumans(null, true)
                            : 'No renewal date';
                    @endphp
                    <div class="px-5 py-4 grid grid-cols-1 md:grid-cols-3 items-center gap-3">
                        <div class="min-w-0 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="{{ $serviceTypeClass($service->type) }}">{{ ucfirst($service->type ?? 'Service') }}</span>
                                <span class="{{ $serviceStatusClass($service->status) }}">{{ ucfirst($service->status ?? 'Status') }}</span>
                            </div>
                            <div class="font-semibold text-text-base truncate">{{ $service->name ?: '—' }}</div>
                            <div class="text-[12px] text-text-subtle flex flex-wrap gap-2">
                                @if ($service->provider)
                                    <span>Provider: {{ $service->provider }}</span>
                                @endif
                                @if ($service->billing_cycle)
                                    <span>Cycle: {{ $service->billing_cycle }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="min-w-0 space-y-1">
                            <div class="text-sm text-text-base">
                                {{ $renewText }}
                                @if (!is_null($days))
                                    @if ($days < 0)
                                        <span class="oh-pill oh-pill--danger text-[11px] ml-2">Overdue</span>
                                    @elseif ($days <= 30)
                                        <span class="oh-pill oh-pill--warning text-[11px] ml-2">Due soon</span>
                                    @endif
                                @endif
                            </div>
                            <div class="text-[12px] text-text-subtle flex flex-wrap gap-2">
                                @if ($service->cost_amount)
                                    @php
                                        $formattedCost = '$' . number_format($service->cost_amount, 2);
                                    @endphp
                                    <span>Cost: {{ $formattedCost }}</span>
                                @endif
                                @if (!is_null($balance))
                                    <span>Balance: {{ number_format($balance, 2) }}</span>
                                @endif
                                @if ($service->handling)
                                    <span class="oh-pill oh-pill--muted text-[11px]">
                                        {{ $service->handling === 'client' ? 'Client pays' : ($service->handling === 'agency_reimburse' ? 'We pay · reimburse' : 'We handle') }}
                                    </span>
                                @endif
                            </div>
                            @if (!empty($service->notes))
                                <div class="text-[12px] text-text-subtle line-clamp-2">
                                    {{ $service->notes }}
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center justify-end gap-2 md:justify-end">
                            <a href="{{ route('tenant.services.show', ['tenant' => $tenantId, 'service' => $service->id]) }}"
                                class="oh-icon-btn oh-tooltip" data-tooltip="View" aria-label="View service">
                                <i class="fa-solid fa-circle-info text-[12px]"></i>
                            </a>
                            <button type="button" class="oh-icon-btn oh-tooltip js-edit-service"
                                data-tooltip="Edit service" aria-label="Edit service"
                                data-action="{{ route('tenant.services.update', ['tenant' => $tenantId, 'service' => $service->id]) }}"
                                data-name="{{ $service->name }}"
                                data-type="{{ $service->type }}"
                                data-status="{{ $service->status }}"
                                data-provider="{{ $service->provider }}"
                                data-provider_url="{{ $service->provider_url }}"
                                data-billing="{{ $service->billing_cycle }}"
                                data-renewal="{{ optional($service->renewal_date)->format('Y-m-d') }}"
                                data-registrar="{{ $service->meta['registrar'] ?? '' }}"
                                data-domain_name="{{ $service->meta['domain_name'] ?? '' }}"
                                data-handling="{{ $service->handling }}"
                                data-reminder_days="{{ $service->reminder_days }}"
                                data-notes="{{ $service->notes }}">
                                <i class="fa-solid fa-pen-to-square text-[12px]"></i>
                            </button>
                            <form method="POST" action="{{ route('tenant.services.destroy', ['tenant' => $tenantId, 'service' => $service->id]) }}"
                                onsubmit="return confirm('Remove this service?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="oh-icon-btn oh-tooltip text-rose-600" data-tooltip="Delete" aria-label="Delete service">
                                    <i class="fa-solid fa-trash text-[12px]"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-sm text-text-subtle">
                        No services yet. Add hosting, domain, maintenance, or retainer services to track renewals.
                    </div>
                @endforelse
            </div>
            @if ($services->hasPages())
                <div class="px-5 py-3 border-t border-border-default/60 text-sm text-text-subtle space-y-3">
                    <div>Showing {{ $services->firstItem() }} to {{ $services->lastItem() }} of {{ $services->total() }} results</div>
                    <div class="flex items-center justify-between">
                        @if ($services->onFirstPage())
                            <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                        @else
                            <a href="{{ $services->previousPageUrl() }}" class="oh-btn">Previous</a>
                        @endif
                        @if ($services->hasMorePages())
                            <a href="{{ $services->nextPageUrl() }}" class="oh-btn">Next</a>
                        @else
                            <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('serviceModal');
            const modalCard = document.getElementById('serviceModalCard');
            const openBtn = document.getElementById('openServiceModal');
            const closeBtns = [document.getElementById('closeServiceModal'), document.getElementById('closeServiceModal2')];
            const form = document.getElementById('serviceForm');
            const methodInput = document.getElementById('serviceFormMethod');
            const titleEl = document.getElementById('serviceModalTitle');
            const fields = {
                name: document.getElementById('serviceName'),
                type: document.getElementById('serviceType'),
                status: document.getElementById('serviceStatus'),
                billing: document.getElementById('serviceBilling'),
                renewal: document.getElementById('serviceRenewal'),
                provider: document.getElementById('serviceProvider'),
                providerUrl: document.getElementById('serviceProviderUrl'),
                registrar: document.getElementById('serviceRegistrar'),
                domain: document.getElementById('serviceDomainName'),
                handling: document.getElementById('serviceHandling'),
                reminder: document.getElementById('serviceReminder'),
                notes: document.getElementById('serviceNotes'),
            };
            const domainFields = document.getElementById('domainFields');
            const createAction = "{{ route('tenant.companies.services.store', ['tenant' => $tenantId, 'company' => $company->id]) }}";

            const toggleDomainFields = () => {
                if (!domainFields) return;
                domainFields.classList.toggle('hidden', fields.type.value !== 'domain');
            };

            const setBodyLock = (locked) => {
                document.body.classList.toggle('overflow-hidden', locked);
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                setBodyLock(false);
            };

            const openModal = (mode = 'create', data = {}) => {
                if (!modal) return;
                modal.classList.remove('hidden');
                setBodyLock(true);

                titleEl.textContent = mode === 'edit' ? 'Edit Service' : 'Add Service';
                form.action = mode === 'edit' ? data.action || createAction : createAction;
                methodInput.value = mode === 'edit' ? 'PATCH' : 'POST';

                fields.name.value = data.name || '';
                fields.type.value = data.type || '';
                fields.status.value = data.status || 'active';
                fields.billing.value = data.billing || '';
                fields.renewal.value = data.renewal || '';
                fields.provider.value = data.provider || '';
                fields.providerUrl.value = data.provider_url || '';
                fields.registrar.value = data.registrar || '';
                fields.domain.value = data.domain_name || '';
                fields.handling.value = data.handling || '';
                fields.reminder.value = data.reminder_days || '';
                fields.notes.value = data.notes || '';

                toggleDomainFields();
                setTimeout(() => fields.name.focus(), 50);
            };

            if (openBtn) {
                openBtn.addEventListener('click', () => openModal('create', {}));
            }

            document.querySelectorAll('.js-edit-service').forEach(btn => {
                btn.addEventListener('click', () => {
                    const data = btn.dataset;
                    openModal('edit', {
                        action: data.action,
                        name: data.name,
                        type: data.type,
                        status: data.status,
                        billing: data.billing,
                        renewal: data.renewal,
                        provider: data.provider,
                        provider_url: data.provider_url,
                        registrar: data.registrar,
                        domain_name: data.domain_name,
                        notes: data.notes,
                    });
                });
            });

            closeBtns.forEach(btn => btn && btn.addEventListener('click', closeModal));
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            }
            if (fields.type) {
                fields.type.addEventListener('change', toggleDomainFields);
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
@endpush
@endsection
