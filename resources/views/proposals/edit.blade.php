@extends('layouts.app')

@section('title', 'Edit Proposal')

@section('content')
    @php
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="space-y-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Sales</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit proposal</h1>
                <p class="text-sm text-text-subtle mt-1">Update the proposal details before sending.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tenant.proposals.show', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}" class="oh-btn oh-btn--ghost w-fit">
                    Cancel
                </a>

                <a href="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}"
                    class="oh-btn">View All Proposals</a>
                <form method="POST"
                    action="{{ route('tenant.proposals.templates.store', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                    data-template-save>
                    @csrf
                    <input type="hidden" name="name" value="">
                    <button type="button" class="oh-btn w-full justify-start" data-template-save-trigger>Save as
                        template</button>
                </form>

            </div>
        </div>

        <form method="POST"
            action="{{ route('tenant.proposals.update', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}" data-proposal-form>
            @csrf
            @method('PUT')
            @include('proposals._form', [
                'proposal' => $proposal,
                'projects' => $projects,
                'clients' => $clients,
                'tenantId' => $tenantId,
                'tenant' => $tenant ?? null,
                'leads' => $leads ?? collect(),
            ])
        </form>
    </div>
@endsection
