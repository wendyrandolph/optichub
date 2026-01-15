@props(['tenant' => null, 'exportType' => null])

@php
    $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
@endphp

<div class="flex items-center justify-between gap-2 mb-4">
    <div class="mb-4">
        <a href="{{ route('tenant.reports.index', ['tenant' => $tenantParam]) }}"
            class="oh-btn oh-btn--ghost inline-flex items-center gap-2 text-sm font-medium text-brand-primary hover:text-brand-secondary transition">
            <i class="fa-solid fa-arrow-left text-[13px]"></i>
            Back to Reports
        </a>
    </div>
    @if (!empty($exportType))
        <div class="mb-4">
            <a href="{{ route('tenant.reports.export', ['tenant' => $tenantParam, 'type' => $exportType] + request()->query()) }}"
                class="oh-btn oh-btn--ghost text-blue-900"><i class="fa-solid fa-file-arrow-down mr-2 text-blue-900"></i>
                Export
                CSV</a>
        </div>
    @endif
</div>
