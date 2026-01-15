@extends('layouts.app')

@section('title', 'Add Opportunity')

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? request()->route('tenant'));
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;
        $stages = ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];
    @endphp
    <div class="oh-page max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Pipeline</p>
                <h1 class="text-2xl font-semibold text-text-base">New Opportunity</h1>
                <p class="text-sm text-text-subtle">Create a pipeline record and set the next follow-up.</p>
            </div>
            <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
        </header>

        <section class="oh-card p-6 space-y-6">
            @if ($errors->any())
                <div class="rounded-xl bg-rose-50 text-rose-800 p-3 ring-1 ring-rose-200 text-sm">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('tenant.opportunities.store', ['tenant' => $tenantId]) }}"
                class="space-y-6">
                @csrf

                {{-- Basics --}}
                <div class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Basics</h2>
                        <p class="text-xs text-text-subtle mt-1">Core details for tracking the opportunity.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Title</span>
                            <input name="title" required value="{{ old('title') }}"
                                class="oh-input h-10">
                            <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Stage</span>
                            <select name="stage" id="oppStage"
                                class="oh-select h-10">
                                @foreach ($stages as $s)
                                    <option value="{{ $s }}" @selected(old('stage', 'new') === $s)>{{ ucfirst($s) }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Estimated value</span>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle text-xs">$</span>
                                <input type="number" step="0.01" min="0" name="estimated_value"
                                    value="{{ old('estimated_value') }}"
                                    class="oh-input h-10 pl-6 pr-3">
                            </div>
                            <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Expected close date</span>
                            <input type="date" name="expected_close_date" value="{{ old('expected_close_date') }}"
                                class="oh-input h-10">
                            <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                        </label>
                    </div>
                </div>

                {{-- Link --}}
                <div class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">People & Company</h2>
                        <p class="text-xs text-text-subtle mt-1">Connect the opportunity to the right people and company.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Owner</span>
                            <select name="owner_id"
                                class="oh-select h-10">
                                <option value="">Unassigned</option>
                                @foreach ($owners ?? [] as $u)
                                    @php
                                        $ownerName =
                                            trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?:
                                            $u->username ?? 'User';
                                    @endphp
                                    <option value="{{ $u->id }}" @selected(old('owner_id') == $u->id)>{{ $ownerName }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Lead</span>
                            <select name="lead_id" id="oppLead"
                                class="oh-select h-10">
                                <option value="">None</option>
                                @foreach ($leads ?? [] as $l)
                                    @php
                                        $leadCompanyId = $l->company_id ?? null;
                                        $leadCompanyName = $l->company->company_name ?? '';
                                    @endphp
                                    <option value="{{ $l->id }}" data-company-id="{{ $leadCompanyId }}"
                                        data-company-name="{{ $leadCompanyName }}" @selected(old('lead_id') == $l->id)>
                                        {{ $l->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle">Auto-fills company when the lead has one.</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Company (optional)</span>
                            <select name="company_id" id="oppCompany"
                                class="oh-select h-10">
                                <option value="">None</option>
                                @foreach ($companies ?? [] as $c)
                                    <option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>
                                        {{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle">Leave blank if not a client yet.</span>
                        </label>
                    </div>
                </div>

                {{-- Follow-up --}}
                <div class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Follow-up</h2>
                        <p class="text-xs text-text-subtle mt-1">This drives reminders and pipeline.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Next step</span>
                            <input name="next_step" value="{{ old('next_step') }}"
                                class="oh-input h-10">
                            <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Next follow-up</span>
                            <input type="datetime-local" name="next_followup_at" id="oppFollowup"
                                value="{{ old('next_followup_at') }}"
                                class="oh-input h-10">
                            <span class="text-[11px] text-text-subtle">Required unless stage is Won/Lost.</span>
                        </label>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-2">
                    <h2 class="text-sm font-semibold text-text-base">Notes</h2>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Internal notes</span>
                        <textarea name="notes" rows="4" class="oh-textarea">{{ old('notes') }}</textarea>
                        <span class="text-[11px] text-text-subtle opacity-0">Helper text</span>
                    </label>
                </div>

                {{-- Lost reason (conditional) --}}
                <div id="lostReasonBlock" class="space-y-2 hidden">
                    <h2 class="text-sm font-semibold text-text-base">Lost reason</h2>
                    <textarea name="lost_reason" rows="2"
                        class="w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm border border-border-default focus:ring-1 focus:ring-brand-primary">{{ old('lost_reason') }}</textarea>
                </div>

                {{-- After saving --}}
                <details class="rounded-lg border border-border-default/70 bg-surface-card/60 p-4">
                    <summary class="text-sm font-semibold text-text-base cursor-pointer">After saving…</summary>
                    <div class="mt-3 space-y-2 text-sm text-text-base">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="create_followup_task" value="1"
                                {{ old('create_followup_task') ? 'checked' : '' }}>
                            <span>Create a follow-up task (placeholder)</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="add_activity_note" value="1"
                                {{ old('add_activity_note') ? 'checked' : '' }}>
                            <span>Log an activity note: “Opportunity created”</span>
                        </label>
                        <label class="flex items-center gap-2 text-text-subtle">
                            <input type="checkbox" disabled>
                            <span>Send internal notification (coming soon)</span>
                        </label>
                    </div>
                </details>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}"
                        class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const leadSelect = document.getElementById('oppLead');
            const companySelect = document.getElementById('oppCompany');
            const stageSelect = document.getElementById('oppStage');
            const lostBlock = document.getElementById('lostReasonBlock');
            const followup = document.getElementById('oppFollowup');

            function syncCompany() {
                const option = leadSelect?.options[leadSelect.selectedIndex];
                const companyId = option?.dataset.companyId;
                if (companyId) {
                    companySelect.value = companyId;
                }
            }

            function toggleLost() {
                if (!stageSelect) return;
                const isLost = stageSelect.value === 'lost';
                lostBlock?.classList.toggle('hidden', !isLost);
                if (followup) {
                    followup.disabled = stageSelect.value === 'won';
                }
            }

            leadSelect?.addEventListener('change', syncCompany);
            stageSelect?.addEventListener('change', toggleLost);
            toggleLost();
        });
    </script>
@endpush
