@php
    /** @var \App\Models\ClientCompany $company */
    $users = $users ?? collect();
    $contactsForSelect = $contactsForSelect ?? collect();
    $statusOptions = $statusOptions ?? ['prospect' => 'Prospect', 'active' => 'Active', 'past' => 'Past'];
    $billingTypes = $billingTypes ?? ['hourly' => 'Hourly', 'retainer' => 'Retainer', 'project' => 'Project', 'na' => 'N/A'];
    $communicationOptions = $communicationOptions ?? ['email' => 'Email', 'phone' => 'Phone', 'chat' => 'Chat', 'video' => 'Video'];
@endphp

<div class="text-xs font-semibold uppercase tracking-wide text-text-subtle mb-1">Company details</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Company name --}}
    <label class="grid gap-1 text-sm">
        <span class="text-text-subtle">Company name</span>
        <input type="text" name="company_name" value="{{ old('company_name', $company->company_name) }}"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary"
            required>
    </label>

    {{-- Industry --}}
    <label class="grid gap-1 text-sm">
        <span class="text-text-subtle">Industry</span>
        <input type="text" name="industry" value="{{ old('industry', $company->industry) }}"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
    </label>

    {{-- Client status --}}
    <label class="grid gap-1 text-sm">
        <span class="text-text-subtle">Client status</span>
        <select name="client_status"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
            <option value="">Select status</option>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('client_status', $company->client_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    {{-- Account owner --}}
    <label class="grid gap-1 text-sm">
        <span class="text-text-subtle">Account owner</span>
        <select name="account_owner_id"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
            <option value="">Unassigned</option>
            @foreach ($users as $user)
                @php
                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    $label = $fullName !== '' ? $fullName : ($user->username ?? $user->email ?? ('User #' . $user->id));
                @endphp
                <option value="{{ $user->id }}" @selected(old('account_owner_id', $company->account_owner_id) == $user->id)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    {{-- Website --}}
    <label class="grid gap-1 text-sm">
        <span class="text-text-subtle">Website</span>
        <input type="text" name="website" value="{{ old('website', $company->website) }}"
            placeholder="https://example.com"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
    </label>

    {{-- Phone --}}
    <label class="grid gap-1 text-sm">
        <span class="text-text-subtle">Phone</span>
        <input type="text" name="phone" value="{{ old('phone', $company->phone) }}"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
    </label>

    {{-- Address --}}
    <label class="grid gap-1 text-sm md:col-span-2">
        <span class="text-text-subtle">Address</span>
        <input type="text" name="address" value="{{ old('address', $company->address) }}"
            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
    </label>

    {{-- Primary contact (only if available) --}}
    @if ($contactsForSelect->count() > 0)
        <label class="grid gap-1 text-sm md:col-span-2">
            <span class="text-text-subtle">Primary contact</span>
            <select name="primary_contact_id"
                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                <option value="">None</option>
                @foreach ($contactsForSelect as $contact)
                    @php
                        $fullName = trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? '')) ?: ($contact->email ?? 'Contact');
                    @endphp
                    <option value="{{ $contact->id }}" @selected(old('primary_contact_id', $company->primary_contact_id) == $contact->id)>
                        {{ $fullName }}
                    </option>
                @endforeach
            </select>
        </label>
    @endif

    {{-- Internal Notes --}}
    <label class="grid gap-1 text-sm md:col-span-2">
        <span class="text-text-subtle">Internal notes</span>
        <textarea name="notes" rows="4"
            class="w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm
                         border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">{{ old('notes', $company->notes) }}</textarea>
        <span class="text-[11px] text-text-subtle">
            Private notes about this client company — not visible to client.
        </span>
    </label>
</div>

{{-- Account details (collapsible) --}}
<details class="mt-4 rounded-2xl border border-border-default/70 bg-surface-card/60 p-4 space-y-3">
    <summary class="cursor-pointer text-sm font-semibold text-text-base">Account details (optional)</summary>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
        <label class="grid gap-1 text-sm">
            <span class="text-text-subtle">Billing type</span>
            <select name="billing_type"
                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                <option value="">Select billing</option>
                @foreach ($billingTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('billing_type', $company->billing_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

        <label class="grid gap-1 text-sm">
            <span class="text-text-subtle">Maintenance plan</span>
            <input type="text" name="maintenance_plan" value="{{ old('maintenance_plan', $company->maintenance_plan) }}"
                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
        </label>

        <label class="grid gap-1 text-sm">
            <span class="text-text-subtle">Renewal date</span>
            <input type="date" name="renewal_date" value="{{ old('renewal_date', optional($company->renewal_date)->format('Y-m-d')) }}"
                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
        </label>

        <label class="grid gap-1 text-sm">
            <span class="text-text-subtle">Preferred communication</span>
            <select name="preferred_communication"
                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                <option value="">Select preference</option>
                @foreach ($communicationOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('preferred_communication', $company->preferred_communication) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-1 text-sm md:col-span-2">
            <span class="text-text-subtle">Timezone</span>
            <input type="text" name="timezone" value="{{ old('timezone', $company->timezone) }}"
                placeholder="e.g., America/New_York"
                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
        </label>
    </div>
</details>
