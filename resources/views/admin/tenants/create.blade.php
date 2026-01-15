@extends('layouts.app')

@section('title', 'Create Tenant')

@section('content')
    @php
        $timezoneOptions = \DateTimeZone::listIdentifiers();
        $selectedTz = old('timezone', 'America/Denver');
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
                <h1 class="text-2xl font-semibold text-text-base">Create tenant</h1>
                <p class="text-sm text-text-subtle mt-1">Add a new workspace for a customer.</p>
            </div>
            <a href="{{ route('admin.tenants.index') }}" class="oh-btn">Cancel</a>
        </div>

        {{-- Form --}}
        <div class="oh-card border border-border-default/70 shadow-sm rounded-2xl p-4 md:p-5">
            <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-4">
                @csrf

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Workspace name</span>
                    <input type="text" name="name" value="{{ old('name') }}" required class="oh-input h-10">
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Website (optional)</span>
                    <input type="text" name="website" value="{{ old('website') }}" class="oh-input h-10">
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Phone (optional)</span>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="oh-input h-10">
                </label>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Timezone</span>
                    <select name="timezone" class="oh-select h-10">
                        @foreach ($timezoneOptions as $tz)
                            <option value="{{ $tz }}" @selected($selectedTz === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                    <span class="text-[11px] text-text-subtle">Used for renewals, reminders, and date displays across this workspace.</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-border-default/60">
                    <a href="{{ route('admin.tenants.index') }}" class="oh-btn">Back to list</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save tenant</button>
                </div>
            </form>
        </div>
    </div>
@endsection
