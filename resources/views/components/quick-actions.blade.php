@props(['range' => 'wtd', 'rangeLabel' => 'WTD'])

@php
    $tenantParam =
        request()->route('tenant') ??
        (function () {
            if (function_exists('tenant') && tenant()) {
                return tenant()->getKey();
            }
            return auth()->user()->tenant_id ?? null;
        })();
    $currentRange = $range ?? 'wtd';
@endphp

<div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    {{-- Quick actions (left) --}}
    <div class="min-w-0">
        @if ($tenantParam)
            <nav class="flex flex-wrap gap-2" aria-label="Quick actions">
                <a href="{{ route('tenant.time.create', ['tenant' => $tenantParam]) }}" class="oh-btn">
                    <i class="fa-regular fa-clock mr-1.5"></i> Log Time
                </a>

                <a href="{{ route('tenant.tasks.create', ['tenant' => $tenantParam]) }}" class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-list-check mr-1.5 text-xs"></i> New Task
                </a>

                <a href="{{ route('tenant.projects.create', ['tenant' => $tenantParam]) }}" class="oh-btn">
                    <i class="fa-regular fa-square-plus mr-1.5 text-xs"></i> New Project
                </a>

                <a href="{{ route('tenant.companies.create', ['tenant' => $tenantParam]) }}" class="oh-btn">
                    <i class="fa-regular fa-building mr-1.5"></i> New Client Company
                </a>
            </nav>
        @else
            <span class="text-sm text-status-danger">Tenant not resolved — cannot render quick actions.</span>
        @endif
    </div>

    {{-- Range tabs (right on md+) --}}
    <div class="md:flex md:justify-end">
        <nav class="oh-segment w-full overflow-x-auto whitespace-nowrap [-webkit-overflow-scrolling:touch] md:w-auto"
            aria-label="Date range">
            @foreach (['today', 'wtd', 'mtd', '30d'] as $r)
                <a href="{{ request()->fullUrlWithQuery(['range' => $r]) }}"
                    class="oh-segment__item inline-flex items-center justify-center px-3 py-2 text-sm {{ $currentRange === $r ? 'is-active' : '' }}">
                    {{ strtoupper($r) }}
                </a>
            @endforeach
        </nav>
    </div>
</div>
