@extends('layouts.trades')

@section('title', 'Edit Job Template')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit Job Template</h1>
                <p class="text-sm text-text-subtle mt-1">Update the details for this template.</p>
            </div>
            <a href="{{ route('tenant.trades.job-templates.show', ['tenant' => $tenantKey, 'job_template' => $template->id]) }}"
                class="oh-btn">Back</a>
        </div>

        <form method="POST"
            action="{{ route('tenant.trades.job-templates.update', ['tenant' => $tenantKey, 'job_template' => $template->id]) }}"
            class="oh-card p-5 space-y-6">
            @csrf
            @method('PUT')

            @include('trades.job-templates._form', ['tenant' => $tenant, 'template' => $template])

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('tenant.trades.job-templates.show', ['tenant' => $tenantKey, 'job_template' => $template->id]) }}"
                    class="oh-btn">Cancel</a>
                <button class="oh-btn oh-btn--primary" type="submit">Save changes</button>
            </div>
        </form>
    </div>
@endsection
