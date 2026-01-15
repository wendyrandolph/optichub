@extends('layouts.app')

@section('title', 'Service Details')

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? request()->route('tenant'));
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;
        $service = $service;
        $company = $service->company;
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('tenant.companies.services.index', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                    class="inline-flex items-center text-xs text-text-subtle hover:text-text-base mb-1">
                    <i class="fa-solid fa-arrow-left mr-1"></i>
                    Back to services
                </a>
                <h1 class="text-2xl font-semibold text-text-base flex items-center gap-2">
                    {{ $service->name ?? ucfirst($service->type) ?? 'Service' }}
                    <span class="oh-pill oh-pill--muted text-[11px]">{{ ucfirst($service->type ?? 'Type') }}</span>
                </h1>
                <p class="text-sm text-text-subtle mt-1">For {{ $company->company_name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.companies.show', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                    class="oh-btn">View company</a>
            </div>
        </header>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="xl:col-span-2 space-y-4">
                <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm p-5 space-y-3">
                    <h2 class="text-sm font-semibold text-text-base">Service details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div><div class="text-text-subtle text-xs uppercase">Provider</div><div class="text-text-base">{{ $service->provider ?: '—' }}</div></div>
                        <div><div class="text-text-subtle text-xs uppercase">Status</div><div><span class="oh-pill oh-pill--muted">{{ ucfirst($service->status ?? 'active') }}</span></div></div>
                        <div><div class="text-text-subtle text-xs uppercase">Billing cycle</div><div class="text-text-base">{{ $service->billing_cycle ?: '—' }}</div></div>
                        <div><div class="text-text-subtle text-xs uppercase">Renewal date</div><div class="text-text-base">{{ optional($service->renewal_date)->format('M j, Y') ?? '—' }}</div></div>
                        @php $meta = $service->meta ?? []; @endphp
                        @if ($service->type === 'domain')
                            <div><div class="text-text-subtle text-xs uppercase">Domain</div><div class="text-text-base">{{ $meta['domain_name'] ?? '—' }}</div></div>
                            <div><div class="text-text-subtle text-xs uppercase">Registrar</div><div class="text-text-base">{{ $meta['registrar'] ?? '—' }}</div></div>
                            <div><div class="text-text-subtle text-xs uppercase">Auto renew</div><div class="text-text-base">{{ !empty($meta['auto_renew']) ? 'Yes' : 'No' }}</div></div>
                        @endif
                        @if ($service->type === 'hosting')
                            <div><div class="text-text-subtle text-xs uppercase">Plan</div><div class="text-text-base">{{ $meta['host_plan'] ?? '—' }}</div></div>
                        @endif
                        @if ($service->type === 'maintenance')
                            <div class="md:col-span-2"><div class="text-text-subtle text-xs uppercase">Plan / Scope</div><div class="text-text-base">{{ $meta['maintenance_cadence'] ?? '—' }}</div></div>
                        @endif
                        @if ($service->type === 'retainer')
                            <div><div class="text-text-subtle text-xs uppercase">Retainer type</div><div class="text-text-base">{{ $meta['retainer_type'] ?? '—' }}</div></div>
                            <div><div class="text-text-subtle text-xs uppercase">Included</div><div class="text-text-base">{{ $meta['retainer_amount'] ?? '—' }}</div></div>
                            <div><div class="text-text-subtle text-xs uppercase">Rollover</div><div class="text-text-base">{{ !empty($meta['rollover_allowed']) ? 'Enabled' : 'Disabled' }}</div></div>
                            <div><div class="text-text-subtle text-xs uppercase">Rollover cap</div><div class="text-text-base">{{ $meta['rollover_cap'] ?? '—' }}</div></div>
                        @endif
                    </div>
                </section>

                {{-- Quick edit (toggle) --}}
                <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-border-default/60">
                        <h2 class="text-sm font-semibold text-text-base">Edit service</h2>
                        <button type="button" id="toggleEditPanel" class="oh-btn oh-btn--ghost text-xs">
                            Edit Details
                        </button>
                    </div>
                    <div id="editServicePanel" class="hidden px-5 py-4">
                        <form method="POST" action="{{ route('tenant.services.update', ['tenant' => $tenantId, 'service' => $service->id]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            @csrf
                            @method('PATCH')
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Name</span>
                                <input name="name" value="{{ old('name', $service->name) }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Type</span>
                                <select name="type" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                    @foreach (['domain','hosting','maintenance','retainer','other'] as $type)
                                        <option value="{{ $type }}" @selected(old('type', $service->type) === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Status</span>
                                <select name="status" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                    @foreach (['active','paused','cancelled'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $service->status) === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Provider</span>
                                <input name="provider" value="{{ old('provider', $service->provider) }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Billing cycle</span>
                                <select name="billing_cycle" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                    <option value="">Select</option>
                                    @foreach (['monthly'=>'Monthly','annual'=>'Annual','one_time'=>'One-time','custom'=>'Custom'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('billing_cycle', $service->billing_cycle) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Renewal date</span>
                                <input type="date" name="renewal_date" value="{{ old('renewal_date', optional($service->renewal_date)->format('Y-m-d')) }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Cost amount</span>
                                <input type="number" step="0.01" name="cost_amount" value="{{ old('cost_amount', $service->cost_amount) }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Cost currency</span>
                                <input name="cost_currency" value="{{ old('cost_currency', $service->cost_currency) }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Registrar (domain)</span>
                                <input name="registrar" value="{{ old('registrar', $meta['registrar'] ?? '') }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Domain name</span>
                                <input name="domain_name" value="{{ old('domain_name', $meta['domain_name'] ?? '') }}" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-text-subtle text-xs uppercase">Auto renew</span>
                                <select name="auto_renew" class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                    <option value="0" @selected(!($meta['auto_renew'] ?? false))>No</option>
                                    <option value="1" @selected($meta['auto_renew'] ?? false)>Yes</option>
                                </select>
                            </label>
                            <label class="grid gap-1 md:col-span-2">
                                <span class="text-text-subtle text-xs uppercase">Notes</span>
                                <textarea name="notes" rows="3" class="w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">{{ old('notes', $service->notes) }}</textarea>
                            </label>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <a href="{{ route('tenant.companies.services.index', ['tenant' => $tenantId, 'company' => $company->id]) }}" class="oh-btn">Cancel</a>
                                <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-text-base">Activity log</h2>
                        <button type="button" id="openLogModal" class="oh-btn oh-btn--ghost text-xs">Add log</button>
                    </div>
                    <div class="divide-y divide-border-default/60">
                        @forelse ($service->logs as $log)
                            <div class="py-3 text-sm flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-text-base">{{ optional($log->occurred_at)->format('M j, Y') ?? '—' }}</div>
                                    <div class="text-text-subtle">{{ ucfirst($log->log_type ?? 'note') }} — {{ $log->description ?: '—' }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @php
                                        $timeLabel = null;
                                        if (!is_null($log->hours)) {
                                            $totalMinutes = (int) round($log->hours * 60);
                                            $hrs = intdiv($totalMinutes, 60);
                                            $mins = $totalMinutes % 60;
                                            if ($hrs && $mins) {
                                                $timeLabel = "{$hrs}h {$mins}m";
                                            } elseif ($hrs) {
                                                $timeLabel = "{$hrs}h";
                                            } else {
                                                $timeLabel = "{$mins}m";
                                            }
                                        }
                                    @endphp
                                    <div class="text-text-base font-semibold">
                                        @if ($timeLabel)
                                            {{ $timeLabel }}
                                        @elseif (!is_null($log->amount))
                                            {{ $log->amount }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                    <button type="button" class="oh-icon-btn oh-tooltip js-edit-log" data-tooltip="Edit log" aria-label="Edit log"
                                        data-action="{{ route('tenant.services.logs.update', ['tenant' => $tenantId, 'log' => $log->id]) }}"
                                        data-occurred="{{ optional($log->occurred_at)->format('Y-m-d') }}"
                                        data-type="{{ $log->log_type }}"
                                        data-hours="{{ $log->hours }}"
                                        data-amount="{{ $log->amount }}"
                                        data-description="{{ $log->description }}">
                                        <i class="fa-solid fa-pen-to-square text-[12px]"></i>
                                    </button>
                                    <form method="POST" action="{{ route('tenant.services.logs.destroy', ['tenant' => $tenantId, 'log' => $log->id]) }}"
                                        onsubmit="return confirm('Delete this log entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="oh-icon-btn oh-tooltip text-rose-600" data-tooltip="Delete log" aria-label="Delete log">
                                            <i class="fa-solid fa-trash text-[12px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="py-3 text-sm text-text-subtle">
                                No log entries yet.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="space-y-4 xl:sticky xl:top-6">
                <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm p-4 space-y-2">
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Renewal warnings</div>
                    @if (!empty($warnings))
                        @foreach ($warnings as $warn)
                            <div class="oh-pill oh-pill--warning text-xs">{{ $warn }}</div>
                        @endforeach
                    @else
                        <div class="text-sm text-text-subtle">No upcoming renewals.</div>
                    @endif
                </section>

                @if (!is_null($balance))
                    <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm p-4 space-y-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Retainer balance</div>
                        <div class="text-2xl font-semibold text-text-base">
                            {{ number_format($balance, 2) }}
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm p-4 space-y-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">Internal Notes</div>
                    @if ($service->notes)
                        <p class="text-sm text-text-subtle whitespace-pre-line">{{ $service->notes }}</p>
                    @else
                        <p class="text-sm text-text-subtle">No internal notes.</p>
                    @endif
                </section>
            </div>
        </div>
    </div>

    {{-- Add Log Modal --}}
    <div id="logModal" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm">
        <div class="min-h-full flex items-center justify-center px-4 py-6">
            <div class="oh-card rounded-2xl border border-border-default/70 shadow-lg w-[92vw] sm:w-full max-w-xl max-h-[85vh] overflow-hidden bg-surface-card bg-white flex flex-col"
                role="dialog" aria-modal="true" aria-labelledby="logModalTitle">
                <div class="flex items-center justify-between px-5 py-4 border-b border-border-default/60">
                    <h2 id="logModalTitle" class="text-lg font-semibold text-text-base">Add log</h2>
                    <button type="button" id="closeLogModal" class="oh-icon-btn" aria-label="Close">
                        <i class="fa-solid fa-xmark text-[14px]"></i>
                    </button>
                </div>
                <form id="logForm" method="POST" action="{{ route('tenant.services.logs.store', ['tenant' => $tenantId, 'service' => $service->id]) }}"
                    class="flex-1 flex flex-col px-5 pt-4">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="logFormMethod">
                    <div class="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-4 pb-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Date</span>
                            <input type="date" name="occurred_at" id="logDate"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Type</span>
                            <select name="log_type" id="logType"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                                <option value="note">Note</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="retainer_usage">Retainer usage</option>
                                <option value="renewal">Renewal</option>
                            </select>
                        </label>
                        <div class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Time (optional)</span>
                            <div class="flex gap-2 flex-wrap">
                                <input type="number" step="0.01" name="hours" id="logHours"
                                    class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary flex-1 min-w-[120px]"
                                    placeholder="e.g., 0.5">
                                <select id="logTimeUnit"
                                    class="h-10 rounded-lg bg-surface-card text-text-base px-2 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary min-w-[110px]">
                                    <option value="hours">Hours</option>
                                    <option value="minutes">Minutes</option>
                                </select>
                            </div>
                        </div>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Amount (optional)</span>
                            <input type="number" step="0.01" name="amount" id="logAmount"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        </label>
                        <label class="grid gap-1 text-sm md:col-span-2">
                            <span class="text-text-subtle">Description</span>
                            <textarea name="description" id="logDescription" rows="3"
                                class="w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary"></textarea>
                        </label>
                    </div>
                    <div class="md:col-span-2 flex items-center justify-end gap-2 bg-surface-card py-3 border-t border-border-default/60 sticky bottom-0">
                        <button type="button" id="closeLogModal2" class="oh-btn">Cancel</button>
                        <button type="submit" class="oh-btn oh-btn--primary">Save log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('toggleEditPanel');
            const panel = document.getElementById('editServicePanel');
            if (toggleBtn && panel) {
                toggleBtn.addEventListener('click', () => {
                    const isHidden = panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', !isHidden);
                    toggleBtn.textContent = isHidden ? 'Hide edit' : 'Edit Details';
                });
            }

            const logModal = document.getElementById('logModal');
            const openLog = document.getElementById('openLogModal');
            const closeLogBtns = [document.getElementById('closeLogModal'), document.getElementById('closeLogModal2')];
            const logForm = document.getElementById('logForm');
            const logMethod = document.getElementById('logFormMethod');
            const logTitle = document.getElementById('logModalTitle');
            const logFields = {
                date: document.getElementById('logDate'),
                type: document.getElementById('logType'),
                hours: document.getElementById('logHours'),
                timeUnit: document.getElementById('logTimeUnit'),
                amount: document.getElementById('logAmount'),
                description: document.getElementById('logDescription'),
            };
            const lockBody = (locked) => document.body.classList.toggle('overflow-hidden', locked);
            const closeLog = () => {
                logModal.classList.add('hidden');
                lockBody(false);
                // reset form
                if (logForm) logForm.reset();
                if (logMethod) logMethod.value = 'POST';
                if (logTitle) logTitle.textContent = 'Add log';
                if (logForm) logForm.action = "{{ route('tenant.services.logs.store', ['tenant' => $tenantId, 'service' => $service->id]) }}";
            };
            const openLogModalFn = () => {
                logModal.classList.remove('hidden');
                lockBody(true);
                if (logFields.date) logFields.date.focus();
            };
            if (openLog && logModal) {
                openLog.addEventListener('click', openLogModalFn);
            }
            document.querySelectorAll('.js-edit-log').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (!logModal || !logForm) return;
                    logModal.classList.remove('hidden');
                    lockBody(true);
                    logTitle.textContent = 'Edit log';
                    logForm.action = btn.dataset.action;
                    logMethod.value = 'PATCH';
                    logFields.date.value = btn.dataset.occurred || '';
                    logFields.type.value = btn.dataset.type || 'note';
                    logFields.hours.value = btn.dataset.hours || '';
                    logFields.amount.value = btn.dataset.amount || '';
                    logFields.description.value = btn.dataset.description || '';
                    logFields.date.focus();
                });
            });
            closeLogBtns.forEach(btn => btn && btn.addEventListener('click', closeLog));
            if (logModal) {
                logModal.addEventListener('click', (e) => {
                    if (e.target === logModal) closeLog();
                });
            }
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && logModal && !logModal.classList.contains('hidden')) {
                    closeLog();
                }
            });

            if (logForm) {
                logForm.addEventListener('submit', () => {
                    if (logFields.timeUnit && logFields.timeUnit.value === 'minutes' && logFields.hours) {
                        const minutes = parseFloat(logFields.hours.value || '0');
                        if (!isNaN(minutes)) {
                            logFields.hours.value = minutes / 60;
                        }
                    }
                });
            }
        });
    </script>
@endpush
