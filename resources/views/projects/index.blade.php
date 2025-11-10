@extends('layouts.app')

@section('content')
    @php
        $q = request('q', '');
        $st = request('status', '');
        $so = request('sort', 'recent');
        $hasFilters = $q !== '' || $st !== '' || $so !== 'recent';

        $tenantParam = request()->route('tenant');
        $tenantId = $tenantParam instanceof \App\Models\Tenant ? $tenantParam->getKey() : (int) $tenantParam;

        $getStatusClass = function (string $status) {
            $s = strtolower(str_replace([' ', '/'], ['-', ''], $status));
            return match (true) {
                str_contains($s, 'open'),
                str_contains($s, 'active')
                    => 'bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
                str_contains($s, 'closed'),
                str_contains($s, 'complete')
                    => 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800',
                str_contains($s, 'lead'),
                str_contains($s, 'opp')
                    => 'bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-200 dark:border-purple-800',
                default
                    => 'bg-gray-100 text-gray-700 border border-gray-200 dark:bg-slate-800/60 dark:text-slate-200 dark:border-slate-700',
            };
        };
    @endphp

    <div class="px-4 py-8 md:px-6 lg:px-8 bg-surface-page min-h-screen">
        <div class="max-w-7xl mx-auto space-y-8">

            {{-- Header --}}
            <header class="mb-3">
                <h1 class="text-[28px] leading-tight font-semibold text-text-base">Projects Overview</h1>
                <p class="mt-1 text-sm text-text-subtle">All projects in your workspace.</p>
            </header>

            {{-- Quick Actions Toolbar (centered text) --}}
            @php
                $tenantId =
                    request()->route('tenant') instanceof \App\Models\Tenant
                        ? request()->route('tenant')->getKey()
                        : (int) request()->route('tenant');
            @endphp
            <div class="max-w-7xl mx-auto space-y-8 flex flex-row gap-6 items-center">

                {{-- Header --}}
                <nav class="rounded-lg bg-surface-card/60 px-3 py-2 flex flex-wrap gap-2 items-center mb-6">
                    <span class="text-xs text-text-subtle mr-1">Quick actions</span>

                    @php
                        $btnBase = 'inline-flex items-center justify-center h-9 px-3 rounded-md text-sm
                  border transition-colors duration-150
                  bg-white text-gray-800 border-gray-200 hover:bg-gray-50
                  dark:bg-surface-card/70 dark:text-text-base dark:border-border-default';
                    @endphp

                    <a href="{{ route('tenant.tasks.create', ['tenant' => $tenantId]) }}" class="{{ $btnBase }}">
                        + New Task
                    </a>

                    <a href="{{ route('tenant.time.create', ['tenant' => $tenantId]) }}" class="{{ $btnBase }}">
                        Log Time
                    </a>

                    <a href="{{ route('tenant.invoices.create', ['tenant' => $tenantId]) }}" class="{{ $btnBase }}">
                        New Invoice
                    </a>

                    <a href="{{ route('tenant.leads.create', ['tenant' => $tenantId]) }}" class="{{ $btnBase }}">
                        New Lead
                    </a>
                </nav>

                {{-- KPI Cards --}}
                <section class="w-1/2 flex flex-row flex-grow justify-evenly gap-4">
                    <div class="w-1/3 rounded-lg p-5 bg-cws-dark text-white hover:brightness-110 transition">
                        <div class="flex items-center justify-between">
                            <i class="fa-solid fa-layer-group text-2xl p-3 rounded-full bg-blue-600"></i>
                            <p class="text-4xl font-bold">{{ $totalProjects ?? 0 }}</p>
                        </div>
                        <p class="mt-3 text-xs tracking-wide uppercase text-white/90">Total Projects</p>
                    </div>

                    <div class="w-1/3 rounded-lg p-5 bg-cws-mid text-white hover:brightness-110 transition">
                        <div class="flex items-center justify-between">
                            <i class="fa-solid fa-user-plus text-2xl p-3 rounded-full bg-green-600"></i>
                            <p class="text-4xl font-bold">{{ $projectsThisWeek ?? 0 }}</p>
                        </div>
                        <p class="mt-3 text-xs tracking-wide uppercase text-white/90">Projects This Week</p>
                    </div>

                    <div class="w-1/3 rounded-lg p-5 bg-cws-light text-white hover:brightness-110 transition">
                        <div class="flex items-center justify-between">
                            <i class="fa-solid fa-chart-line text-2xl p-3 rounded-full bg-purple-600"></i>
                            <p class="text-4xl font-bold">{{ $openProjects ?? 0 }}</p>
                        </div>
                        <p class="mt-3 text-xs tracking-wide uppercase text-white/90">Open Projects</p>
                    </div>
                </section>

            </div>
            {{-- All Projects --}}
            <div>
                <h2 class="text-2xl font-bold text-text-base mb-2">All Projects</h2>
                <p class="text-text-subtle mb-4">Here you will find all the projects that are currently in our database.
                </p>
            </div>

            {{-- Toolbar --}}
            <div class="w-full rounded-xl bg-surface-card/70 p-4 md:p-6">
                <form class="flex flex-col md:flex-row items-stretch md:items-center gap-3 justify-between" method="GET"
                    action="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}">

                    <input name="q" value="{{ $q }}" placeholder="Search project or client…"
                        class="w-1/4 h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm
                      focus:outline-none focus:ring-1 focus:ring-brand-primary">


                    <select name="status"
                        class="w-1/4 rounded-md px-3 h-9  text-sm bg-white text-gray-700 border border-gray-200 hover:bg-gray-50
                 dark:bg-surface-card/60 dark:text-text-base dark:border-border-default focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        <option value="" @selected($st === '')>All statuses</option>
                        <option value="open" @selected($st === 'open')>Open</option>
                        <option value="closed" @selected($st === 'closed')>Closed</option>
                    </select>

                    <select name="sort"
                        class="w-1/4 h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        <option value="recent" @selected($so === 'recent')>Recently updated</option>
                        <option value="name_asc" @selected($so === 'name_asc')>Name A–Z</option>
                        <option value="name_desc" @selected($so === 'name_desc')>Name Z–A</option>
                        <option value="start_desc"@selected($so === 'start_desc')>Start date ↓</option>
                    </select>

                    <div class="flex gap-2 w-1/3 md:w-auto">
                        <button type="submit"
                            class="w-1/2 h-10 px-4 rounded-lg bg-gradient-to-b from-brand-primary to-blue-700 text-white text-sm font-medium">
                            <i class="fa fa-filter mr-2"></i> Filter
                        </button>

                        @if ($hasFilters)
                            <a href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}"
                                class="h-10 px-4 rounded-lg bg-surface-card/60 hover:bg-surface-card/90 text-text-base text-sm font-medium">
                                Reset
                            </a>
                        @endif

                        <a href="{{ route('tenant.projects.create', ['tenant' => $tenantId]) }}"
                            class="h-10 px-4 rounded-lg bg-green-600 hover:bg-green-700 text-white text-sm font-medium">
                            <i class="fa fa-plus mr-2"></i> New Project
                        </a>

                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="mt-6 overflow-x-auto rounded-xl bg-surface-card/70">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-text-subtle">
                        <tr class="border-b border-border-default/40">
                            <th class="px-6 py-3">Project Name</th>
                            <th class="px-6 py-3">Organization</th>
                            <th class="px-6 py-3">Client</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Start Date</th>
                            <th class="px-6 py-3">End Date</th>
                            <th class="px-6 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default/30">
                        @forelse ($projects as $project)
                            <tr class="hover:bg-surface-card/60 transition-colors">
                                <td class="px-6 py-4 font-medium text-text-base">
                                    {{ $project['project_name'] ?? '' }}
                                </td>
                                <td class="px-6 py-4 text-text-subtle">
                                    {{ $project['organization_name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-text-subtle">
                                    {{ $project['client_name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-medium
             bg-gray-100 text-gray-700 border border-gray-200
             dark:bg-slate-800/60 dark:text-slate-200 dark:border-slate-700 {{ $getStatusClass($project['status'] ?? 'N/A') }}">
                                        {{ $project['status'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-text-subtle">
                                    @if (!empty($project['start_date']) && strtotime($project['start_date']) > 0)
                                        {{ date('M j, Y', strtotime($project['start_date'])) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-text-subtle">
                                    @if (!empty($project['end_date']) && strtotime($project['end_date']) > 0)
                                        {{ date('M j, Y', strtotime($project['end_date'])) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center gap-3 text-text-subtle">
                                        <a href="{{ route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $project['id']]) }}"
                                            class="hover:text-blue-600 dark:hover:text-blue-300" title="View">
                                            <i class="fa-solid fa-circle-info"></i>
                                        </a>
                                        <a href="{{ route('tenant.projects.edit', ['tenant' => $tenantId, 'project' => $project['id']]) }}"
                                            class="hover:text-green-600 dark:hover:text-green-300" title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <form method="POST"
                                            action="{{ route('tenant.projects.destroy', ['tenant' => $tenantId, 'project' => $project['id']]) }}"
                                            onsubmit="return confirm('Are you sure you want to delete this project?');"
                                            class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="hover:text-red-600 dark:hover:text-red-300"
                                                title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-text-subtle">
                                    No projects found.
                                    @if ($hasFilters)
                                        <a href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}"
                                            class="text-blue-700 dark:text-blue-300 hover:underline ml-2">Reset
                                            filters</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination (preserve filters) --}}
            @if (method_exists($projects, 'appends'))
                <div>
                    {{ $projects->appends(request()->only(['q', 'status', 'sort']))->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection
