@extends('layouts.app')

@section('title', 'New Recurring Invoice')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Billing</p>
                <h1 class="text-2xl font-semibold text-text-base">Create Recurring Invoice</h1>
                <p class="mt-1 text-sm text-text-subtle">Set the schedule and default line item.</p>
            </div>
            <a href="{{ route('tenant.invoices.recurring.index', ['tenant' => $tenantId]) }}" class="oh-btn">Back</a>
        </div>

        <form method="POST" action="{{ route('tenant.invoices.recurring.store', ['tenant' => $tenantId]) }}" class="oh-card space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Client</span>
                    <select name="contact_id" class="oh-select h-10" required>
                        <option value="">Select client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: $client->email }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Project (optional)</span>
                    <select name="project_id" class="oh-select h-10">
                        <option value="">No project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Frequency</span>
                    <select name="frequency" class="oh-select h-10">
                        <option value="weekly">Weekly</option>
                        <option value="monthly" selected>Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Interval</span>
                    <input type="number" name="interval" min="1" max="12" value="1" class="oh-input h-10">
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Due days</span>
                    <input type="number" name="due_days" min="0" max="60" value="14" class="oh-input h-10">
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Day of week (weekly)</span>
                    <select name="day_of_week" class="oh-select h-10">
                        <option value="">Auto</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                    </select>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Day of month (monthly)</span>
                    <input type="number" name="day_of_month" min="1" max="28" value="1" class="oh-input h-10">
                </label>
            </div>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Invoice title (optional)</span>
                <input type="text" name="title" class="oh-input h-10">
            </label>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <label class="grid gap-1 text-sm md:col-span-2">
                    <span class="text-text-subtle">Line item description</span>
                    <input type="text" name="items[0][description]" class="oh-input h-10" required>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Quantity</span>
                    <input type="number" step="0.01" min="0.01" name="items[0][quantity]" value="1" class="oh-input h-10" required>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Unit price</span>
                    <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="0" class="oh-input h-10" required>
                </label>
                <label class="inline-flex items-center gap-2 text-sm md:col-span-3">
                    <input type="checkbox" name="auto_send" value="1" class="rounded border-[rgb(var(--border-default))]">
                    Auto-send invoices on creation
                </label>
            </div>

            <label class="grid gap-1 text-sm">
                <span class="text-text-subtle">Notes (optional)</span>
                <textarea name="notes" rows="3" class="oh-textarea"></textarea>
            </label>

            <div class="flex justify-end gap-2">
                <a href="{{ route('tenant.invoices.recurring.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">Save</button>
            </div>
        </form>
    </div>
@endsection
