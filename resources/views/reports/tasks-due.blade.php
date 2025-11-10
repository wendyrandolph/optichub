@extends('layouts.app')
@section('title', 'Tasks Due')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $window = $filters['window'] ?? 'next14';
        $status = $filters['status'] ?? '';
        $assignee = $filters['assignee'] ?? '';
        $sort = $filters['sort'] ?? 'due_date';
        $dir = $filters['dir'] ?? 'asc';
        $qText = $filters['qText'] ?? '';

        $th = function ($key, $label) use ($sort, $dir, $tenantParam, $window, $status, $assignee, $qText, $filters) {
            $next = $sort === $key && $dir === 'asc' ? 'desc' : 'asc';
            $url = route('tenant.admin.reports.tasks.due', [
                'tenant' => $tenantParam,
                'sort' => $key,
                'dir' => $next,
                'window' => $window,
                'status' => $status,
                'assignee' => $assignee,
                'q' => $qText,
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
            ]);
            $arrow = $sort === $key ? ($dir === 'asc' ? '▲' : '▼') : '';
            return '<a href="' .
                $url .
                '" class="inline-flex items-center gap-1">' .
                $label .
                ' <span class="text-xs">' .
                $arrow .
                '</span></a>';
        };
    @endphp

    <x-reports.back-button :tenant="$tenant" />

    {{-- Header / Filters --}}
    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Tasks Due</h1>
                <p class="text-sm text-text-subtle">Overdue, today, and upcoming windows</p>
            </div>

            <form method="GET" action="{{ route('tenant.admin.reports.tasks.due', ['tenant' => $tenantParam]) }}"
                class="flex flex-wrap items-center gap-2">
                <select name="window" class="oh-select"
                    onchange="document.querySelectorAll('[data-custom-range]').forEach(el=>el.classList.toggle('hidden', this.value!=='custom'))">
                    @foreach (['overdue' => 'Overdue', 'today' => 'Today', 'next7' => 'Next 7d', 'next14' => 'Next 14d', 'next30' => 'Next 30d', 'all' => 'All', 'custom' => 'Custom'] as $k => $lbl)
                        <option value="{{ $k }}" @selected(($filters['window'] ?? 'next14') === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <div class="{{ ($filters['window'] ?? '') === 'custom' ? '' : 'hidden' }} flex items-center gap-2"
                    data-custom-range>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="oh-select">
                    <span class="text-text-subtle text-xs">to</span>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="oh-select">
                </div>

                <select name="status" class="oh-select">
                    <option value="" @selected($status === '')>All statuses</option>
                    @foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <input name="assignee" value="{{ $assignee }}" placeholder="Assignee ID" class="oh-select"
                    style="width:140px">
                <input name="q" value="{{ $qText }}" placeholder="Search title / project" class="oh-select"
                    style="width:220px">

                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <button class="oh-btn bg-brand-primary text-white">Apply</button>
                <a class="oh-btn oh-btn--ghost"
                    href="{{ route('tenant.admin.reports.tasks.due', ['tenant' => $tenantParam]) }}">Reset</a>


            </form>
        </div>
    </div>

    {{-- Quick chips --}}
    <div class="oh-card mb-4 flex flex-wrap items-center gap-2">
        <span class="chip chip--muted">Overdue: {{ (int) ($counts['overdue'] ?? 0) }}</span>
        <span class="chip chip--muted">Today: {{ (int) ($counts['today'] ?? 0) }}</span>
        <span class="chip chip--muted">Next 7d: {{ (int) ($counts['next7'] ?? 0) }}</span>
    </div>
    <div class="oh-card mb-4">
        <x-oh-chart :config="$dueConfig" class="w-full h-64 mb-6" />
    </div>
    {{-- Table --}}
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">{!! $th('due_date', 'Due') !!}</th>
                    <th class="py-2 pr-4">{!! $th('title', 'Title') !!}</th>
                    <th class="py-2 pr-4">{!! $th('project', 'Project') !!}</th>
                    <th class="py-2 pr-4">{!! $th('assignee', 'Assignee') !!}</th>
                    <th class="py-2 pr-2">{!! $th('status', 'Status') !!}</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                @forelse ($rows as $r)
                    <tr class="hover:bg-[rgb(var(--card-accent-bg))]">
                        <td class="py-2 pr-4">{{ \Carbon\Carbon::parse($r->due_date)->format('M d, Y') }}</td>
                        <td class="py-2 pr-4">
                            <a class="text-brand-primary hover:underline"
                                href="{{ route('tenant.tasks.show', ['tenant' => $tenantParam, 'task' => $r->id]) }}">
                                {{ $r->title ?? '—' }}
                            </a>
                        </td>
                        <td class="py-2 pr-4">{{ $r->project_name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->assignee_name ?? '—' }}</td>
                        <td class="py-2 pr-2">
                            <span class="chip chip--muted">{{ ucwords(str_replace('_', ' ', $r->status)) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-text-subtle">No tasks match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">
            {{ $rows->links() }}
        </div>
    </div>
@endsection
