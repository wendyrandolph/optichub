@extends('layouts.app')

@section('title', 'Organizations')

@section('content')
    @php
        $tp = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        $q = request('q', '');
        $so = request('sort', 'recent');
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Header + CTA --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Organizations</h1>
                <p class="text-sm text-text-subtle mt-1">Your companies and clients in one place.</p>
            </div>
            <a href="{{ route('tenant.organizations.create', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
              bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                <i class="fa-solid fa-plus mr-2 text-xs"></i> New Organization
            </a>
        </header>
        {{-- KPI Row --}}
        @php
            $k = $kpis ?? [
                'total' => 0,
                'updated30' => 0,
                'with_site' => 0,
                'with_phone' => 0,
                'by_industry' => collect(),
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Total Organizations</div>
                <div class="text-2xl font-semibold text-text-base">{{ $k['total'] }}</div>
            </div>

            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Updated (Last 30d)</div>
                <div class="text-2xl font-semibold text-text-base">{{ $k['updated30'] }}</div>
            </div>

            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Has Website</div>
                <div class="text-2xl font-semibold text-text-base">{{ $k['with_site'] }}</div>
            </div>

            <div class="rounded-xl bg-surface-card/70 border border-border-default/60 p-4">
                <div class="text-xs text-text-subtle mb-1">Has Phone</div>
                <div class="text-2xl font-semibold text-text-base">{{ $k['with_phone'] }}</div>
            </div>
        </div>

        {{-- Industry Chips --}}
        @if (!empty($k['by_industry']) && count($k['by_industry']))
            <div class="flex flex-wrap gap-2">
                @foreach ($k['by_industry'] as $industry => $count)
                    @php
                        $label = $industry ?: 'Unspecified';
                        // Soft brandy pill
                        $pill = 'bg-blue-100 text-blue-700 border border-blue-200';
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $pill }}">
                        {{ \Illuminate\Support\Str::limit($label, 22) }}
                        <span class="ml-1 text-[11px] opacity-75">({{ $count }})</span>
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Toolbar --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60">
            <form method="GET" action="{{ route('tenant.organizations.index', ['tenant' => $tenantId]) }}"
                class="p-4 md:p-5 flex flex-col md:flex-row md:flex-wrap gap-3 md:items-center">

                {{-- Search (slightly narrower so buttons breathe) --}}
                <div class="flex-1 md:flex-none md:w-[320px]">
                    <label class="sr-only" for="q">Search</label>
                    <input id="q" name="q" value="{{ $q }}"
                        placeholder="Search organization or industry…"
                        class="w-full h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                </div>

                {{-- Sort --}}
                <div class="md:w-[220px]">
                    <label class="sr-only" for="sort">Sort</label>
                    <select id="sort" name="sort"
                        class="h-10 w-full rounded-lg bg-surface-card text-text-base px-3 text-sm
                       border border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        <option value="recent" @selected($so === 'recent')>Recently updated</option>
                        <option value="name_asc" @selected($so === 'name_asc')>Name A–Z</option>
                        <option value="name_desc" @selected($so === 'name_desc')>Name Z–A</option>
                        <option value="city_asc" @selected($so === 'city_asc')>Location A–Z</option>
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap gap-2 md:ml-auto">
                    <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
                       bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                        <i class="fa-solid fa-filter mr-2 text-xs"></i> Filter
                    </button>

                    @if ($q || $so !== 'recent')
                        <a href="{{ route('tenant.organizations.index', ['tenant' => $tenantId]) }}"
                            class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm
                    bg-surface-card/60 hover:bg-surface-card/90 text-text-base">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table Card --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-surface-card">
                    <tr class="text-left text-text-subtle">
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Industry</th>
                        <th class="px-6 py-3 font-medium">Location</th>
                        <th class="px-6 py-3 font-medium">Website</th>
                        <th class="px-6 py-3 font-medium text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-default/60">
                    @forelse ($organizations as $org)
                        @php
                            $orgId = data_get($org, 'id');
                            $name = data_get($org, 'name', '—');
                            $ind = data_get($org, 'industry', '—');
                            $loc = data_get($org, 'location', '—');
                            $site = data_get($org, 'website');
                        @endphp
                        <tr class="hover:bg-surface-accent/30">
                            <td class="px-6 py-3 text-text-base">
                                <a href="{{ route('tenant.organizations.show', ['tenant' => $tenantId, 'organization' => $orgId]) }}"
                                    class="text-blue-700 hover:underline">
                                    {{ $name }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-text-base">{{ $ind }}</td>
                            <td class="px-6 py-3 text-text-base">{{ $loc }}</td>
                            <td class="px-6 py-3">
                                @if ($site)
                                    <a href="{{ $site }}" target="_blank" rel="noopener"
                                        class="text-blue-700 hover:underline">
                                        {{ \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $site), 28) }}
                                    </a>
                                @else
                                    <span class="text-text-subtle">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-center gap-3 text-text-subtle">
                                    <a href="{{ route('tenant.organizations.show', ['tenant' => $tenantId, 'organization' => $orgId]) }}"
                                        class="hover:text-green-700 dark:hover:text-green-300" title="View">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </a>

                                    <a href="{{ route('tenant.organizations.edit', ['tenant' => $tenantId, 'organization' => $orgId]) }}"
                                        class="hover:text-green-700 dark:hover:text-green-300" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST"
                                        action="{{ route('tenant.organizations.destroy', ['tenant' => $tenantId, 'organization' => $orgId]) }}"
                                        onsubmit="return confirm('Delete this organization?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="hover:text-red-700 dark:hover:text-red-300"
                                            title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-text-subtle">
                                No organizations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if (method_exists($organizations, 'links'))
                <div class="px-4 py-3 border-t border-border-default/60">
                    {{ $organizations->appends(request()->only('q', 'sort'))->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
