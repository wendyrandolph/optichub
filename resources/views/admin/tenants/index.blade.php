@extends('layouts.app')

@section('title', 'Tenants')

@section('content')
    @php
        $q = request('q', '');
        $status = request('status', 'all');
        $plan = request('plan', 'all');

        $k = $kpis ?? [
            'total' => 0,
            'active' => 0,
            'trialing' => 0,
            'with_branding' => 0,
        ];
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings</p>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-text-base">Tenants &amp; Workspaces</h1>
                    <p class="text-sm text-text-subtle mt-1">Monitor who’s live, trialing, and which workspaces are branded.</p>
                </div>
                <a href="{{ route('admin.tenants.create') }}" class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus mr-2 text-[12px]"></i> New Tenant
                </a>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="oh-card border border-border-default/60 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wide text-text-subtle">All Workspaces</p>
                <p class="text-[13px] text-text-subtle">
                    {{ $tenants->count() }} shown
                    @if (method_exists($tenants, 'total'))
                        · {{ $tenants->total() }} total
                    @endif
                </p>
            </div>
            <div class="oh-card border border-border-default/60 rounded-xl p-4">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Active</p>
                <p class="text-xl font-semibold text-text-base">{{ $k['active'] }}</p>
            </div>
            <div class="oh-card border border-border-default/60 rounded-xl p-4">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Trialing</p>
                <p class="text-xl font-semibold text-text-base">{{ $k['trialing'] }}</p>
            </div>
            <div class="oh-card border border-border-default/60 rounded-xl p-4">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Branded</p>
                <p class="text-xl font-semibold text-text-base">{{ $k['with_branding'] }}</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="oh-card border border-border-default/70 shadow-sm rounded-2xl">
            <form method="GET" action="{{ route('admin.tenants.index') }}"
                class="w-full flex flex-col gap-3 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-3 lg:flex-1">
                    <div class="w-full lg:flex-1 lg:max-w-[320px]">
                        <label class="sr-only" for="q">Search tenants</label>
                        <input id="q" name="q" value="{{ $q }}" placeholder="Search by workspace name, domain, or owner…"
                            class="oh-input h-10 w-full">
                    </div>
                    <div class="w-full sm:w-[180px]">
                        <label class="sr-only" for="status">Status</label>
                        <select id="status" name="status" class="oh-select h-10 w-full">
                            <option value="all" @selected($status === 'all')>Status: All</option>
                            <option value="active" @selected($status === 'active')>Active</option>
                            <option value="trialing" @selected($status === 'trialing')>Trialing</option>
                            <option value="paused" @selected($status === 'paused')>Paused</option>
                            <option value="canceled" @selected($status === 'canceled')>Canceled</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-[180px]">
                        <label class="sr-only" for="plan">Plan</label>
                        <select id="plan" name="plan" class="oh-select h-10 w-full">
                            <option value="all" @selected($plan === 'all')>Plan: All</option>
                            <option value="starter" @selected($plan === 'starter')>Starter</option>
                            <option value="pro" @selected($plan === 'pro')>Pro</option>
                            <option value="studio" @selected($plan === 'studio')>Studio</option>
                            <option value="enterprise" @selected($plan === 'enterprise')>Enterprise</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 justify-start lg:justify-end w-full lg:w-auto">
                    <button type="submit" class="oh-btn w-full sm:w-auto">
                        <i class="fa-solid fa-filter mr-2 text-xs"></i> Apply
                    </button>
                    @if ($q || $status !== 'all' || $plan !== 'all')
                        <a href="{{ route('admin.tenants.index') }}" class="oh-btn w-full sm:w-auto">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="oh-card bg-surface-card/90 border border-border-default/70 shadow-sm rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-border-default/60">
                <p class="text-xs uppercase tracking-wide text-text-subtle mb-0.5">All workspaces</p>
                <p class="text-[13px] text-text-subtle">
                    {{ $tenants->count() }} shown on this page
                    @if (method_exists($tenants, 'total'))
                        · {{ $tenants->total() }} total
                    @endif
                </p>
            </div>

            {{-- Mobile cards --}}
            <div class="md:hidden grid gap-4 sm:grid-cols-2 p-4">
                @forelse ($tenants as $tenant)
                    @php
                        $name = $tenant->name ?? 'Untitled workspace';
                        $domain = $tenant->website ?? ($tenant->domain ?? null);
                        $planName = $tenant->plan_name ?? ($tenant->subscription_plan ?? '—');
                        $statusVal = strtolower($tenant->subscription_status ?? 'inactive');
                        $hasBranding = !empty($tenant->primary_color) || !empty($tenant->secondary_color) || !empty($tenant->accent_color) || !empty($tenant->logo_path);
                        $statusLabel = match ($statusVal) {
                            'active' => 'Active',
                            'trialing' => 'Trialing',
                            'paused' => 'Paused',
                            'canceled' => 'Canceled',
                            default => ucfirst($statusVal),
                        };
                    @endphp
                    <article class="oh-card border border-border-default/70 p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="font-semibold text-text-base truncate">
                                    {{ $name }}
                                </a>
                                @if ($domain)
                                    <p class="text-xs text-text-subtle truncate">{{ $domain }}</p>
                                @endif
                            </div>
                            <span class="oh-pill">{{ $statusLabel }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-text-subtle">
                            <span class="oh-pill">{{ $planName }}</span>
                            @if ($hasBranding)
                                <span class="oh-pill oh-pill--info">Branded</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-btn">View</a>
                            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="oh-btn">Edit</a>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-text-subtle">No tenants found yet.</p>
                @endforelse
            </div>

            {{-- Desktop table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[rgb(var(--surface-muted)/.55)]">
                        <tr class="text-left text-text-subtle border-b border-border-default/60">
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">Domain</th>
                            <th class="px-5 py-3 font-medium">Plan</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border) / .35);">
                        @forelse ($tenants as $tenant)
                            @php
                                $name = $tenant->name ?? 'Untitled workspace';
                                $domain = $tenant->website ?? ($tenant->domain ?? '—');
                                $planName = $tenant->plan_name ?? ($tenant->subscription_plan ?? '—');
                                $statusVal = strtolower($tenant->subscription_status ?? 'inactive');
                                $statusLabel = match ($statusVal) {
                                    'active' => 'Active',
                                    'trialing' => 'Trialing',
                                    'paused' => 'Paused',
                                    'canceled' => 'Canceled',
                                    default => ucfirst($statusVal),
                                };
                            @endphp
                            <tr class="hover:bg-surface-accent/40 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-text-base truncate">{{ $name }}</div>
                                </td>
                                <td class="px-5 py-3 text-text-subtle truncate">{{ $domain }}</td>
                                <td class="px-5 py-3 text-text-subtle">
                                    <span class="oh-pill">{{ $planName }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="oh-pill">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="oh-icon-btn" title="View">
                                            <i class="fa-solid fa-circle-info text-[12px]"></i>
                                        </a>
                                        <a href="{{ route('admin.tenants.edit', $tenant) }}" class="oh-icon-btn" title="Edit">
                                            <i class="fa-solid fa-pen text-[12px]"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.tenants.destroy', $tenant) }}"
                                            onsubmit="return confirm('Delete this tenant?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="oh-icon-btn text-rose-600" title="Delete">
                                                <i class="fa-solid fa-trash text-[12px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-6 text-center text-text-subtle">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (method_exists($tenants, 'links'))
            <div class="flex items-center justify-between text-sm text-text-subtle">
                <div>
                    {{ $tenants->appends(request()->only('q', 'status', 'plan'))->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
