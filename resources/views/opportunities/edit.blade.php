@extends('layouts.app')

@section('title', 'Edit Opportunity')

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
                <h1 class="text-2xl font-semibold text-text-base">Edit Opportunity</h1>
                <p class="text-sm text-text-subtle">Update pipeline details and keep your follow-ups current.</p>
            </div>
            <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                <i class="fa-solid fa-arrow-left mr-2 text-xs" aria-hidden="true"></i>
                View All Opportunities
            </a>
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
            <form method="POST"
                action="{{ route('tenant.opportunities.update', ['tenant' => $tenantId, 'opportunity' => $opportunity->id]) }}"
                class="space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Basics</h2>
                        <p class="text-xs text-text-subtle mt-1">Core information for the opportunity record.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Title</span>
                            <input name="title" required value="{{ old('title', $opportunity->title) }}"
                                class="oh-input h-10">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Stage</span>
                            <select name="stage" class="oh-select h-10">
                                @foreach ($stages as $s)
                                    <option value="{{ $s }}" @selected(old('stage', $opportunity->stage) === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle">Selecting “Lost” requires a reason below.</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Estimated value</span>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle text-xs">$</span>
                                <input type="number" step="0.01" min="0" name="estimated_value"
                                    value="{{ old('estimated_value', $opportunity->estimated_value) }}"
                                    class="oh-input h-10 pl-6 pr-3">
                            </div>
                            <span class="text-[11px] text-text-subtle">Use your best estimate for the full deal value.</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Expected close date</span>
                            <input type="date" name="expected_close_date"
                                value="{{ old('expected_close_date', optional($opportunity->expected_close_date)->format('Y-m-d')) }}"
                                class="oh-input h-10">
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">People & Company</h2>
                        <p class="text-xs text-text-subtle mt-1">Link the opportunity to the right contact and company.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Owner</span>
                            <select name="owner_id" class="oh-select h-10">
                                <option value="">Unassigned</option>
                                @foreach (($owners ?? []) as $u)
                                    @php
                                        $ownerName =
                                            trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: ($u->email ?? 'User');
                                    @endphp
                                    <option value="{{ $u->id }}" @selected(old('owner_id', $opportunity->owner_id) == $u->id)>{{ $ownerName }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Lead</span>
                            <select name="lead_id" id="oppLead" class="oh-select h-10">
                                <option value="">None</option>
                                @foreach (($leads ?? []) as $l)
                                    @php
                                        $leadCompanyId = $l->company_id ?? null;
                                        $leadCompanyName = $l->company->company_name ?? '';
                                    @endphp
                                    <option value="{{ $l->id }}"
                                        data-company-id="{{ $leadCompanyId }}"
                                        data-company-name="{{ $leadCompanyName }}"
                                        @selected(old('lead_id', $opportunity->lead_id) == $l->id)>
                                        {{ $l->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle">Selecting a lead will auto-fill company when available.</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Company (optional)</span>
                            <select name="company_id" id="oppCompany" class="oh-select h-10">
                                <option value="">None</option>
                                @foreach (($companies ?? []) as $c)
                                    <option value="{{ $c->id }}" @selected(old('company_id', $opportunity->company_id) == $c->id)>{{ $c->company_name }}</option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-text-subtle">Leave blank if the lead isn’t a client yet.</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <h2 class="text-sm font-semibold text-text-base">Follow-up</h2>
                        <p class="text-xs text-text-subtle mt-1">Keep next steps and reminders up to date.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Next step</span>
                            <input name="next_step" value="{{ old('next_step', $opportunity->next_step) }}"
                                class="oh-input h-10">
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Next follow-up</span>
                            <input type="datetime-local" name="next_followup_at"
                                value="{{ old('next_followup_at', optional($opportunity->next_followup_at)->format('Y-m-d\\TH:i')) }}"
                                class="oh-input h-10">
                            <span class="text-[11px] text-text-subtle">Required unless stage is Won/Lost.</span>
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <h2 class="text-sm font-semibold text-text-base">Notes & Outcome</h2>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Internal notes</span>
                        <textarea name="notes" rows="4" class="oh-textarea">{{ old('notes', $opportunity->notes) }}</textarea>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Lost reason (only if stage is Lost)</span>
                        <textarea name="lost_reason" rows="2" class="oh-textarea">{{ old('lost_reason', $opportunity->lost_reason) }}</textarea>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
                </div>
            </form>
        </section>
    </div>
@endsection
