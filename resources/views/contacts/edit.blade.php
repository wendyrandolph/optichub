@extends('layouts.app')

@section('title', 'Edit Contact')

@section('content')
    @php
        // Normalize tenant + contact variables
        $tenantId = $tenant ?? (auth()->user()->tenant_id ?? null);
        $contact = $client ?? ($contact ?? null);
    @endphp

    <div class="oh-page max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Contacts</p>
                <h1 class="text-2xl font-semibold text-text-base">
                    Edit contact
                </h1>
                <p class="text-sm text-text-subtle">
                    Update details, assign the company, and choose how this client’s messages appear.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tenant.contacts.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                    Back to contacts
                </a>
                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                    class="oh-btn">
                    Cancel
                </a>
            </div>
        </header>

        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-800">
                <p class="text-sm font-semibold">Please fix the following:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tenant.contacts.update', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
            method="POST" class="oh-card">
            @csrf
            @method('PUT')

            <div class="space-y-8">
                <section class="space-y-4">
                    <div class="space-y-1">
                        <h2 class="text-base font-semibold text-text-base">Contact details</h2>
                        <p class="text-sm text-text-subtle">
                            Keep this person’s profile accurate for invoices, messages, and project updates.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">First name</span>
                            <input name="firstName" value="{{ old('firstName', $contact->firstName) }}" required
                                class="oh-input @error('firstName') ring-2 ring-rose-300 @enderror">
                            @error('firstName')
                                <small class="text-rose-600">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Last name</span>
                            <input name="lastName" value="{{ old('lastName', $contact->lastName) }}" required
                                class="oh-input @error('lastName') ring-2 ring-rose-300 @enderror">
                            @error('lastName')
                                <small class="text-rose-600">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Company</span>
                        <select name="client_company_id"
                            class="oh-select @error('client_company_id') ring-2 ring-rose-300 @enderror">
                            <option value="">(Unassigned)</option>
                            @foreach ($companies ?? [] as $company)
                                <option value="{{ $company->id }}"
                                    @selected(old('client_company_id', $contact->client_company_id) == $company->id)>
                                    {{ $company->company_name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-text-subtle">Assign a company, or leave unassigned.</span>
                        @error('client_company_id')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Email</span>
                            <input type="email" name="email" value="{{ old('email', $contact->email) }}" required
                                class="oh-input @error('email') ring-2 ring-rose-300 @enderror">
                            @error('email')
                                <small class="text-rose-600">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Phone (optional)</span>
                            <input name="phone" value="{{ old('phone', $contact->phone) }}"
                                class="oh-input @error('phone') ring-2 ring-rose-300 @enderror">
                            @error('phone')
                                <small class="text-rose-600">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm max-w-xs">
                        <span class="text-text-subtle">Status</span>
                        @php $status = old('status', $contact->status); @endphp
                        <select name="status"
                            class="oh-select @error('status') ring-2 ring-rose-300 @enderror">
                            <option value="active" @selected($status === 'active')>Active</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                        </select>
                        @error('status')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>
                </section>

                <section class="space-y-4">
                    @php
                        $clientPalette = $portalMessagePalette['client'] ?? ['#1c2e70'];
                        $selectedClientColor = strtolower(old('portal_client_message_color', $contact->portal_client_message_color ?? ''));
                        if ($selectedClientColor === '') {
                            $selectedClientColor = strtolower($clientPalette[0] ?? '');
                        }
                        $teamMap = $portalMessagePalette['teamMap'] ?? [];
                    @endphp
                    <div class="space-y-1">
                        <h2 class="text-base font-semibold text-text-base">Client message theme</h2>
                        <p class="text-sm text-text-subtle">
                            Pick a paired color theme for the client thread in the portal.
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
                            Keep any context that your team should remember about this contact.
                        </p>
                    </div>
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Notes (optional)</span>
                        <textarea name="notes" rows="4"
                            class="oh-textarea @error('notes') ring-2 ring-rose-300 @enderror">{{ old('notes', $contact->notes) }}</textarea>
                        @error('notes')
                            <small class="text-rose-600">{{ $message }}</small>
                        @enderror
                    </label>
                </section>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border-default/70 pt-5 mt-6">
                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $contact->id]) }}"
                    class="oh-btn">
                    Cancel
                </a>
                <button type="submit" class="oh-btn oh-btn--primary">
                    Save changes
                </button>
            </div>
        </form>
    </div>
@endsection
