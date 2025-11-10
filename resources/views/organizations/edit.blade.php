@extends('layouts.app')

@section('title', 'Organizations')

@section('content')
    @php
        // Resolve tenant (model or scalar → id)
        $tp = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        $orgId = data_get($organization, 'id');
    @endphp

    <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

        {{-- Header --}}
        <header class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-text-base">Edit Organization</h2>
            <a href="{{ route('tenant.organizations.show', ['tenant' => $tenantId, 'organization' => $orgId]) }}"
                class="inline-flex items-center h-9 px-3 rounded-lg text-sm bg-surface-card/60 text-text-base hover:bg-surface-card/90">
                <i class="fa fa-arrow-left mr-2"></i> Back to Organization
            </a>
        </header>

        {{-- Flash / errors --}}
        @if ($errors->any())
            <div
                class="rounded-md bg-red-50 border border-red-200 text-red-800 p-3
                dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Card --}}
        <section class="rounded-xl bg-surface-card/70 border border-border-default/60">
            <form method="POST"
                action="{{ route('tenant.organizations.update', ['tenant' => $tenantId, 'organization' => $orgId]) }}"
                class="p-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-text-subtle">Organization Name *</label>
                    <input type="text" id="name" name="name" required
                        value="{{ old('name', data_get($organization, 'name')) }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                <div>
                    <label for="industry" class="block text-sm font-medium text-text-subtle">Industry</label>
                    <input type="text" id="industry" name="industry"
                        value="{{ old('industry', data_get($organization, 'industry')) }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-text-subtle">Location</label>
                    <input type="text" id="location" name="location"
                        value="{{ old('location', data_get($organization, 'location')) }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                <div>
                    <label for="website" class="block text-sm font-medium text-text-subtle">Website</label>
                    <input type="url" id="website" name="website"
                        value="{{ old('website', data_get($organization, 'website')) }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-text-subtle">Phone</label>
                    <input type="tel" id="phone" name="phone"
                        value="{{ old('phone', data_get($organization, 'phone')) }}"
                        class="mt-1 w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-text-subtle">Notes</label>
                    <textarea id="notes" name="notes" rows="4"
                        class="mt-1 w-full rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm
                         border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">{{ old('notes', data_get($organization, 'notes')) }}</textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('tenant.organizations.index', ['tenant' => $tenantId]) }}"
                        class="inline-flex items-center h-10 px-4 rounded-lg text-sm
                  bg-surface-card/60 text-text-base hover:bg-surface-card/90">
                        Cancel
                    </a>

                    <button type="submit"
                        class="inline-flex items-center h-10 px-5 rounded-lg text-sm font-medium text-white
                       bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                        Update Organization
                    </button>
                </div>
            </form>
        </section>

    </div>
@endsection
