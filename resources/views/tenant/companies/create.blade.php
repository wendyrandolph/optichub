@extends('layouts.app')

@section('title', 'New Client Company')

@section('content')
    @php
        $tenantModel = $tenant ?? request()->route('tenant');
        $tenantId = $tenantModel instanceof \App\Models\Tenant ? $tenantModel->id : (int) $tenantModel;
        $tenantId = $tenantId ?: null;
    @endphp

    <div class="oh-page space-y-6 max-w-4xl mx-auto">
        {{-- Header --}}
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.08em] text-text-subtle">Clients</p>
                <h1 class="text-2xl font-semibold text-text-base">New Client Company</h1>
                <p class="text-sm text-text-subtle">Add a company so you can link contacts, projects, and invoices to it.</p>
            </div>
            @if ($tenantId)
                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                        Cancel
                    </a>
                    <button form="company-create-form" type="submit" class="oh-btn oh-btn--primary">
                        Save company
                    </button>
                </div>
            @endif
        </header>

        {{-- Form card --}}
        <section class="oh-card space-y-4">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold mb-1">Please fix the following:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="company-create-form" method="POST"
                action="{{ $tenantId ? route('tenant.companies.store', ['tenant' => $tenantId]) : '#' }}"
                class="space-y-5">
                @csrf

                @include('tenant.companies._form', ['company' => $company])

                <div class="flex items-center justify-end gap-3 pt-2">
                    @if ($tenantId)
                        <a href="{{ route('tenant.companies.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                            Back to list
                        </a>
                    @endif
                    <button type="submit" class="oh-btn oh-btn--primary">
                        Save company
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
