@extends('layouts.app')
@section('title', 'Throughput')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $range = $range ?? ['start' => null, 'end' => null];
    @endphp

    <x-reports.back-button :tenant="$tenant" />

    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Throughput</h1>
                <p class="text-sm text-text-subtle">
                    Tasks completed and opportunities won per week (last 8 weeks).
                </p>
            </div>
        </div>
    </div>

    <div class="oh-card">
        <x-oh-chart :config="$throughputConfig" class="w-full h-64" />
    </div>
@endsection
