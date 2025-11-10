@props(['range' => 'wtd', 'rangeLabel' => 'WTD'])

@php
    $tenantParam =
        request()->route('tenant') ??
        (function () {
            if (function_exists('tenant') && tenant()) {
                return tenant()->getTenantKey();
            }
            return auth()->user()->tenant_id ?? null;
        })();
@endphp

<div class="px-4 sm:px-6 lg:px-8 mt-6 space-y-4">
    {{-- Header row --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-2">
        <div>
            <h1 class="text-2xl font-semibold text-text-base leading-tight">Welcome to the Dashboard!</h1>
            <p class="text-sm text-text-subtle mt-1">Overview of your tasks, projects, and activity for
                {{ $rangeLabel }}.</p>
        </div>

        {{-- Range tabs (right on md+) --}}
        <nav class="flex flex-wrap items-center gap-2" aria-label="Date range">
            @php $currentRange = $range; @endphp
            @foreach (['today', 'wtd', 'mtd', '30d'] as $r)
                <a href="{{ request()->fullUrlWithQuery(['range' => $r]) }}"
                    class="inline-flex items-center justify-center h-9 px-3 rounded-lg text-sm font-medium transition-colors
                  {{ $currentRange === $r ? 'bg-blue-500 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    {{ strtoupper($r) }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Quick actions row (Dashboard Toolbar) --}}
    <div class="oh-panel mb-4">
        <nav class="oh-panel__actions" aria-label="Quick actions">
            @if ($tenantParam)
                <a href="{{ route('tenant.time.create', ['tenant' => $tenantParam]) }}" class="oh-btn oh-btn--ghost">
                    <i class="fa-regular fa-clock mr-1.5"></i> Log Time
                </a>

                <a href="{{ route('tenant.tasks.create', ['tenant' => $tenantParam]) }}" class="oh-btn oh-btn--ghost">
                    <i class="fa-regular fa-list-check mr-1.5"></i> New Task
                </a>

                <a href="{{ route('tenant.projects.create', ['tenant' => $tenantParam]) }}"
                    class="oh-btn oh-btn--ghost">
                    <i class="fa-regular fa-diagram-project mr-1.5"></i> New Project
                </a>

                <a href="{{ route('tenant.organizations.create', ['tenant' => $tenantParam]) }}"
                    class="oh-btn oh-btn--ghost">
                    <i class="fa-regular fa-building mr-1.5"></i> Add Organization
                </a>
            @else
                <span class="text-sm text-status-danger">Tenant not resolved — cannot render quick actions.</span>
            @endif
        </nav>
    </div>

</div>
