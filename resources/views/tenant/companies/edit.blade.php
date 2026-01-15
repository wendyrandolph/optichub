@extends('layouts.app')

@section('title', 'Edit Client Company')

@section('content')
    @php
        $tenantModel = $tenant ?? request()->route('tenant');
        $tenantId = $tenantModel instanceof \App\Models\Tenant ? $tenantModel->id : (int) $tenantModel;
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <header class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">
                    Clients
                </p>
                <h1 class="text-2xl font-semibold text-text-base">Edit Client Company</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Update details for {{ $company->company_name ?? 'this company' }}.
                </p>
            </div>

            <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center justify-center h-9 px-3 rounded-lg text-xs font-medium text-text-base
                      bg-surface-card/90 border border-border-default/70 hover:bg-surface-card transition">
                Back to list
            </a>
        </header>

        {{-- Form card --}}
        <section class="rounded-2xl bg-surface-card border border-border-default/70 shadow-sm p-5 space-y-4">
            @if ($errors->any())
                <div class="mb-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700">
                    <p class="font-semibold mb-1">Please fix the following:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                action="{{ route('tenant.companies.update', ['tenant' => $tenantId, 'company' => $company->id]) }}"
                class="space-y-5">
                @csrf
                @method('PUT')

                @include('tenant.companies._form', ['company' => $company])

                <div class="flex items-center justify-end gap-3 pt-3">
                    <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-lg text-sm font-medium
                              bg-surface-card/80 border border-border-default/70 text-text-base hover:bg-surface-card">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-lg text-sm font-medium text-white
                                   bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                        Save changes
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
