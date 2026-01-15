@extends('layouts.trades')

@section('title', 'Edit Service Location')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3">
            <a href="{{ route('tenant.trades.locations.show', ['tenant' => $tenantKey, 'location' => $location->id]) }}"
                class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                Back to location
            </a>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit Service Location</h1>
                <p class="text-sm text-text-subtle mt-1">Update the address and access details.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form method="POST"
                action="{{ route('tenant.trades.locations.update', ['tenant' => $tenantKey, 'location' => $location->id]) }}"
                class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="label">Location label</label>
                        <input id="label" name="label" class="oh-input h-10"
                            value="{{ old('label', $location->label) }}">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="client_id">Client</label>
                        <select id="client_id" name="client_id" class="oh-select h-10">
                            <option value="">Select client</option>
                            @foreach ($clients as $client)
                                @php
                                    $clientLabel =
                                        trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?:
                                        'Client #' . $client->id;
                                @endphp
                                <option value="{{ $client->id }}" @selected((string) old('client_id', $location->client_id) === (string) $client->id)>
                                    {{ $clientLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="client_company_id">Company</label>
                        <select id="client_company_id" name="client_company_id" class="oh-select h-10">
                            <option value="">(Unassigned)</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('client_company_id', $location->client_company_id) === (string) $company->id)>
                                    {{ $company->company_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-text-subtle">Optional: tie this location to a company account.</p>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="address_line1">Address line 1</label>
                        <input id="address_line1" name="address_line1" class="oh-input h-10" required
                            value="{{ old('address_line1', $location->address_line1) }}">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="address_line2">Address line 2</label>
                        <input id="address_line2" name="address_line2" class="oh-input h-10"
                            value="{{ old('address_line2', $location->address_line2) }}">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="city">City</label>
                        <input id="city" name="city" class="oh-input h-10"
                            value="{{ old('city', $location->city) }}">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="state">State/Region</label>
                        <input id="state" name="state" class="oh-input h-10"
                            value="{{ old('state', $location->state) }}">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="postal_code">Postal code</label>
                        <input id="postal_code" name="postal_code" class="oh-input h-10"
                            value="{{ old('postal_code', $location->postal_code) }}">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="country">Country</label>
                        <input id="country" name="country" class="oh-input h-10"
                            value="{{ old('country', $location->country) }}">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="access_notes">Access notes</label>
                        <textarea id="access_notes" name="access_notes" class="oh-input min-h-[110px]" rows="3">{{ old('access_notes', $location->access_notes) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a href="{{ route('tenant.trades.locations.show', ['tenant' => $tenantKey, 'location' => $location->id]) }}"
                        class="oh-btn">Cancel</a>
                    <button class="oh-btn oh-btn--primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
