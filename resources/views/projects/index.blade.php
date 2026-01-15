{{-- resources/views/tenant/projects/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Projects Overview')

@section('content')
    @php
        use App\Models\Tenant;

        // Resolve tenant safely (consistent with your other pages)
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof Tenant ? $tp->getKey() : (int) $tp;

        // Expect: $projects (Paginator/Collection)
        $projects = $projects ?? collect();

        // Safe KPI fallbacks (override if your controller already provides these)
        $totalProjects =
            $totalProjects ?? (method_exists($projects, 'total') ? $projects->total() : $projects->count());
        $openProjects = $openProjects ?? ($openProjectsCount ?? null);
        $projectsThisWeek = $projectsThisWeek ?? ($projectsThisWeekCount ?? null);

        // Query state
        $q = request('q', '');
        $status = request('status', '');
        $sort = request('sort', 'updated');

        // If controller didn’t pass counts, derive best-effort (collection only)
        if ($openProjects === null && $projects instanceof \Illuminate\Support\Collection) {
            $openProjects = $projects->whereIn('status', ['open', 'active', 'in_progress'])->count();
        }
        if ($projectsThisWeek === null) {
            $projectsThisWeek = $projectsThisWeek ?? 0;
        }

    @endphp
    <div class="oh-page">
        {{-- Header + primary action --}}
        <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-1">
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-text-subtle">Projects</div>
                <h1 class="text-2xl font-semibold text-text-base leading-tight">Projects Overview</h1>
                <p class="text-sm text-text-subtle">All projects in your workspace.</p>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">

                <a href="{{ Route::has('tenant.projects.create') ? route('tenant.projects.create', ['tenant' => $tenantId]) : '#' }}"
                    class="oh-btn oh-btn--primary w-full sm:w-auto text-center">
                    + New Project
                </a>
            </div>
        </header>

        {{-- Quick actions + KPI band (two-column) --}}
        <section class="oh-card">
            <div class="grid gap-4 lg:grid-cols-1 xl:grid-cols-2 lg:items-start">
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold tracking-wide text-text-subtle uppercase">
                            Quick actions & Stats
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- Adjust these routes to match your app --}}
                        <a href="{{ Route::has('tenant.tasks.create') ? route('tenant.tasks.create', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn">
                            + New Task
                        </a>

                        <a href="{{ Route::has('tenant.time.index') ? route('tenant.time.index', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn">
                            Log Time
                        </a>

                        <a href="{{ Route::has('tenant.invoices.create') ? route('tenant.invoices.create', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn">
                            New Invoice
                        </a>

                        <a href="{{ Route::has('tenant.leads.create') ? route('tenant.leads.create', ['tenant' => $tenantId]) : '#' }}"
                            class="oh-btn">
                            New Lead
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full xl:min-w-[520px]">
                    {{-- Uses your existing KPI component + accent bar language --}}
                    <x-kpi-card title="Total Projects" value="{{ number_format((int) $totalProjects) }}"
                        icon="fa-layer-group" colorType="secondary"
                        href="{{ Route::has('tenant.projects.index') ? route('tenant.projects.index', ['tenant' => $tenantId]) : '#' }}" />

                    <x-kpi-card title="Projects This Week" value="{{ number_format((int) $projectsThisWeek) }}"
                        icon="fa-calendar-week" colorType="accent" href="#" />

                    <x-kpi-card title="Open Projects" value="{{ number_format((int) ($openProjects ?? 0)) }}"
                        icon="fa-folder-open" colorType="success"
                        href="{{ Route::has('tenant.projects.index') ? route('tenant.projects.index', ['tenant' => $tenantId, 'status' => 'open']) : '#' }}" />
                </div>
            </div>
        </section>

        {{-- Filters / Search (consistent with Reports + Lead Insights) --}}
        <section class="oh-card">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-text-base">All Projects</h2>
                    <p class="text-sm text-text-subtle mt-1">Search and filter your workspace projects.</p>
                </div>

                <form method="GET"
                    action="{{ Route::has('tenant.projects.index') ? route('tenant.projects.index', ['tenant' => $tenantId]) : url()->current() }}"
                    class="grid gap-2 w-full lg:w-auto grid-cols-2 sm:grid-cols-2 xl:grid-cols-4 items-end">
                    <input class="oh-input w-full col-span-2 xl:col-span-2" name="q" value="{{ $q }}"
                        placeholder="Search project or client…">

                    <select class="oh-select w-full" name="status">
                        <option value="">All statuses</option>
                        @foreach (['open' => 'Open', 'active' => 'Active', 'in_progress' => 'In progress', 'completed' => 'Completed', 'stale' => 'Stale', 'blocked' => 'Blocked'] as $k => $lbl)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                        @endforeach
                    </select>

                    <select class="oh-select w-full" name="sort">
                        @foreach (['updated' => 'Recently updated', 'created' => 'Newest', 'name' => 'Name (A–Z)'] as $k => $lbl)
                            <option value="{{ $k }}" @selected($sort === $k)>{{ $lbl }}</option>
                        @endforeach
                    </select>

                    <div class="flex flex-row gap-2 sm:justify-end w-full col-span-2 xl:col-span-1 mt-1 mb-1">
                        <button class="oh-btn oh-btn--primary w-1/2 sm:w-auto" type="submit">
                            <i class="fa-solid fa-filter text-xs"></i> Filter
                        </button>

                        <a class="oh-btn w-1/2 sm:w-auto"
                            href="{{ Route::has('tenant.projects.index') ? route('tenant.projects.index', ['tenant' => $tenantId]) : url()->current() }}">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </section>

        {{-- Projects table (xl ~1224px+) + cards below that --}}
        <section class="oh-card">
            <div class="overflow-x-auto hidden xl:block">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wide text-text-subtle">
                            <th class="py-3 pr-4 text-left">Project</th>
                            <th class="py-3 pr-4 text-left">Client Company</th>
                            <th class="py-3 pr-4 text-left">Status</th>
                            <th class="py-3 pr-4 text-left">Start</th>
                            <th class="py-3 pr-4 text-left">End</th>
                            <th class="py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y" style="divide-color: rgb(var(--border) / 0.55);">
                        @forelse ($projects as $project)
                            @php
                                // Normalize data (supports arrays or Eloquent)
                                $pid = data_get($project, 'id');
                                $pname =
                                    data_get($project, 'name') ??
                                    (data_get($project, 'project_name') ?? 'Untitled project');
                                $clientName =
                                    data_get($project, 'client.name') ??
                                    (data_get($project, 'company.name') ??
                                        (data_get($project, 'client_company') ??
                                            (data_get($project, 'client_company_name') ?? '—')));

                                $rawStatus = strtolower((string) (data_get($project, 'status') ?? 'open'));

                                $projectColor =
                                    data_get($project, 'color_hex') ??
                                    (data_get($project, 'color') ?? (data_get($project, 'accent_color') ?? null));

                                // fallback if missing
                                $accent = $projectColor ?: 'rgb(var(--brand-secondary))';

                                // Token pills (uses your .oh-pill variants)
                                $pillClass = match ($rawStatus) {
                                    'completed', 'done' => 'oh-pill oh-pill--success',
                                    'stale', 'overdue', 'at_risk' => 'oh-pill oh-pill--warning',
                                    'blocked' => 'oh-pill oh-pill--danger',
                                    'active', 'in_progress' => 'oh-pill oh-pill--info',
                                    default => 'oh-pill',
                                };

                                $start = data_get($project, 'start_date') ?? data_get($project, 'start_at');
                                $end = data_get($project, 'end_date') ?? data_get($project, 'end_at');

                                $startFmt = $start ? \Carbon\Carbon::parse($start)->format('M j, Y') : '—';
                                $endFmt = $end ? \Carbon\Carbon::parse($end)->format('M j, Y') : '—';

                                $showHref = Route::has('tenant.projects.show')
                                    ? route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $pid])
                                    : '#';

                                $editHref = Route::has('tenant.projects.edit')
                                    ? route('tenant.projects.edit', ['tenant' => $tenantId, 'project' => $pid])
                                    : '#';
                            @endphp

                            <tr class="group hover:bg-[rgb(var(--surface-muted))] transition-colors">
                                <td class="py-4 pr-4">
                                    <div class="relative pl-4">
                                        <span class="absolute left-0 top-1 bottom-1 w-[4px] rounded-full"
                                            style="background: {{ $accent }}; opacity: .85;"></span>

                                        <a href="{{ $showHref }}"
                                            class="block font-semibold text-text-base leading-tight hover:underline">
                                            {{ $pname }}
                                        </a>

                                        <div class="text-xs text-text-subtle mt-0.5 truncate">
                                            {{ $clientName }}
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 pr-4 text-text-subtle">
                                    {{ $clientName }}
                                </td>

                                <td class="py-4 pr-4">
                                    <span class="{{ $pillClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $rawStatus)) }}
                                    </span>
                                </td>

                                <td class="py-4 pr-4 text-text-subtle">{{ $startFmt }}</td>
                                <td class="py-4 pr-4 text-text-subtle">{{ $endFmt }}</td>

                                <td class="py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ $showHref }}" class="oh-btn oh-btn--primary" title="View">
                                            <i class="fa-solid fa-circle-info text-xs"></i>
                                        </a>
                                        <a href="{{ $editHref }}" class="oh-btn" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-text-subtle">
                                    No projects found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cards for screens below xl --}}
            <div class="grid gap-3 xl:hidden mt-3 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($projects as $project)
                    @php
                        $pid = data_get($project, 'id');
                        $pname = data_get($project, 'name') ?? (data_get($project, 'project_name') ?? 'Untitled project');
                        $clientName =
                            data_get($project, 'client.name') ??
                            (data_get($project, 'company.name') ??
                                (data_get($project, 'client_company') ?? (data_get($project, 'client_company_name') ?? '—')));
                        $rawStatus = strtolower((string) (data_get($project, 'status') ?? 'open'));
                        $projectColor =
                            data_get($project, 'color_hex') ??
                            (data_get($project, 'color') ?? (data_get($project, 'accent_color') ?? null));
                        $accent = $projectColor ?: 'rgb(var(--brand-secondary))';
                        $pillClass = match ($rawStatus) {
                            'completed', 'done' => 'oh-pill oh-pill--success',
                            'stale', 'overdue', 'at_risk' => 'oh-pill oh-pill--warning',
                            'blocked' => 'oh-pill oh-pill--danger',
                            'active', 'in_progress' => 'oh-pill oh-pill--info',
                            default => 'oh-pill',
                        };
                        $start = data_get($project, 'start_date') ?? data_get($project, 'start_at');
                        $end = data_get($project, 'end_date') ?? data_get($project, 'end_at');
                        $startFmt = $start ? \Carbon\Carbon::parse($start)->format('M j, Y') : '—';
                        $endFmt = $end ? \Carbon\Carbon::parse($end)->format('M j, Y') : '—';
                        $showHref = Route::has('tenant.projects.show')
                            ? route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $pid])
                            : '#';
                        $editHref = Route::has('tenant.projects.edit')
                            ? route('tenant.projects.edit', ['tenant' => $tenantId, 'project' => $pid])
                            : '#';
                    @endphp
                    <article class="oh-card border border-[rgb(var(--border)/0.6)] p-4 space-y-3 relative">
                        <span class="absolute inset-y-4 left-0 w-[4px] rounded-full" style="background: {{ $accent }};"></span>
                        <div class="pl-3">
                            <a href="{{ $showHref }}"
                                class="font-semibold text-text-base leading-tight hover:underline block">{{ $pname }}</a>
                            <div class="text-sm text-text-subtle truncate">{{ $clientName }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pl-3 text-sm">
                            <span class="{{ $pillClass }}">{{ ucfirst(str_replace('_', ' ', $rawStatus)) }}</span>
                            <span class="text-text-subtle">Start: {{ $startFmt }}</span>
                            <span class="text-text-subtle">End: {{ $endFmt }}</span>
                        </div>
                        <div class="flex items-center gap-2 pl-3">
                            <a href="{{ $showHref }}" class="oh-btn oh-btn--primary w-full">View</a>
                            <a href="{{ $editHref }}" class="oh-btn w-full">Edit</a>
                        </div>
                    </article>
                @empty
                    <div class="oh-card bg-[rgb(var(--surface-muted))] text-center text-text-subtle py-6">
                        No projects found.
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if (method_exists($projects, 'links'))
                <div class="mt-4">
                    {{ $projects->withQueryString()->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
