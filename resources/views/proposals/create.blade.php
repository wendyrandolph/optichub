@extends('layouts.app')

@section('title', 'Create Proposal')

@section('content')
    @php
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;
        $proposal = $proposal ?? null;
        $isEdit = (bool) $proposal;
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="space-y-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Sales</p>
                <h1 class="text-2xl font-semibold text-text-base">Create proposal</h1>
                <p class="text-sm text-text-subtle mt-1">Build a structured proposal tied to a project and client.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--ghost w-fit">
                    Cancel
                </a>

                <button type="submit"
                    class="oh-btn oh-btn--primary">{{ $isEdit ? 'Save changes' : 'Save proposal' }}</button>

            </div>
        </div>

        <form method="POST" action="{{ route('tenant.proposals.store', ['tenant' => $tenantId]) }}" data-proposal-form>
            @csrf
            @include('proposals._form', [
                'proposal' => null,
                'projects' => $projects,
                'clients' => $clients,
                'tenantId' => $tenantId,
                'tenant' => $tenant ?? null,
                'leads' => $leads ?? collect(),
                'templates' => $templates ?? [],
                'selectedTemplate' => $selectedTemplate ?? null,
            ])
        </form>
    </div>
@endsection
