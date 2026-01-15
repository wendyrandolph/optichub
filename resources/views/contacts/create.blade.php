@extends('layouts.app')

@section('title', 'Add Contact')

@section('content')
    @php
        $tenantId = $tenant ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="oh-page space-y-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Contacts</p>
                <h1 class="text-2xl font-semibold text-text-base">Add contact</h1>
                <p class="text-sm text-text-subtle">Create a contact you can link to companies, projects, and invoices.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.contacts.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
                <button form="contact-create-form" type="submit" class="oh-btn oh-btn--primary">Save contact</button>
            </div>
        </header>

        @if ($errors->any())
            <div class="oh-card border border-red-200 bg-red-50 text-red-800">
                <p class="text-sm font-semibold">Please fix the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="contact-create-form" action="{{ route('tenant.contacts.store', ['tenant' => $tenantId]) }}"
            method="POST" class="oh-card">
            @csrf

            <div class="space-y-8">
                <section class="space-y-4">
                    <div class="space-y-1">
                        <h2 class="text-base font-semibold text-text-base">Contact details</h2>
                        <p class="text-sm text-text-subtle">
                            Add the core details your team will reference in the workspace and portal.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">First name</span>
                            <input name="firstName" value="{{ old('firstName') }}" required class="oh-input">
                            @error('firstName')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Last name</span>
                            <input name="lastName" value="{{ old('lastName') }}" required class="oh-input">
                            @error('lastName')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Company</span>
                        <select name="client_company_id" class="oh-select">
                            <option value="">(Unassigned)</option>
                            @foreach ($companies ?? [] as $company)
                                <option value="{{ $company->id }}"
                                    @selected(old('client_company_id', $selectedCompany?->id) == $company->id)>
                                    {{ $company->company_name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-text-subtle">Assign a company, or leave unassigned.</span>
                        @error('client_company_id')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Email</span>
                            <input type="email" name="email" value="{{ old('email') }}" required class="oh-input">
                            @error('email')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Phone (optional)</span>
                            <input name="phone" value="{{ old('phone') }}" class="oh-input">
                            @error('phone')
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm max-w-xs">
                        <span class="text-text-subtle">Status</span>
                        <select name="status" class="oh-select">
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                    </label>
                </section>

                <section class="space-y-4">
                    @php
                        $clientPalette = $portalMessagePalette['client'] ?? ['#1c2e70'];
                        $selectedClientColor = strtolower(old('portal_client_message_color', $client->portal_client_message_color ?? ''));
                        if ($selectedClientColor === '') {
                            $selectedClientColor = strtolower($clientPalette[0] ?? '');
                        }
                        $teamMap = $portalMessagePalette['teamMap'] ?? [];
                    @endphp
                    <div class="space-y-1">
                        <h2 class="text-base font-semibold text-text-base">Client message theme</h2>
                        <p class="text-sm text-text-subtle">
                            Choose the paired colors used in the client portal messages.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($clientPalette as $color)
                            @php
                                $muted = $teamMap[strtolower($color)] ?? '#e6eaf2';
                            @endphp
                            <label class="flex items-center gap-2 rounded-lg border border-border-default px-3 py-2">
                                <input type="radio" name="portal_client_message_color" value="{{ $color }}"
                                    @checked($selectedClientColor === strtolower($color))>
                                <span class="flex items-center gap-1">
                                    <span class="h-6 w-6 rounded-md border border-border-default"
                                        style="background-color: {{ $color }}"></span>
                                    <span class="h-6 w-6 rounded-md border border-border-default"
                                        style="background-color: {{ $muted }}"></span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <span class="text-[11px] text-text-subtle">
                        Left swatch is the client bubble; right swatch is the team bubble.
                    </span>
                    @error('portal_client_message_color')
                        <small class="text-rose-600">{{ $message }}</small>
                    @enderror
                </section>

                <section class="space-y-4">
                    <div class="space-y-1">
                        <h2 class="text-base font-semibold text-text-base">Notes</h2>
                        <p class="text-sm text-text-subtle">
                            Keep context your team should remember.
                        </p>
                    </div>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Notes (optional)</span>
                        <textarea name="notes" rows="4" class="oh-textarea min-h-[120px]">{{ old('notes') }}</textarea>
                    </label>
                </section>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border-default/70 pt-5 mt-6">
                <a href="{{ route('tenant.contacts.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                    Cancel
                </a>
                <button type="submit" class="oh-btn oh-btn--primary">Save contact</button>
            </div>
        </form>
    </div>
@endsection
