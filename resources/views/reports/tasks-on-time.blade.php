@extends('layouts.app')
@section('title', 'On-Time Delivery')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $fmt = fn($v) => $v ? \Carbon\Carbon::parse($v)->format('M d, Y') : '—';
    @endphp


    <x-reports.back-button :tenant="$tenant" />
    <div class="oh-card mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">On-Time Delivery</h1>
                <p class="text-sm text-text-subtle">{{ $summary['on_time'] }}/{{ $summary['total'] }} on time
                    ({{ $summary['pct'] }}%)</p>
            </div>
            <form class="flex gap-2" method="GET"
                action="{{ route('tenant.admin.reports.tasks-on-time', ['tenant' => $tenantParam]) }}">
                <select name="range" class="oh-select">
                    @foreach (['wtd' => 'WTD', 'mtd' => 'MTD', 'qtd' => 'QTD', 'ytd' => 'YTD', 'last30' => 'Last 30'] as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['range'] ?? 'wtd') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <input name="assignee" class="oh-select" placeholder="Assignee ID"
                    value="{{ $filters['assigneeId'] ?? '' }}">
                <input name="q" class="oh-select" placeholder="Search title/project"
                    value="{{ $filters['qText'] ?? '' }}">
                <button class="oh-btn bg-brand-primary text-white">Apply</button>
            </form>
        </div>
    </div>
    <div class="oh-card mb-4">
        <x-oh-chart :config="$donutConfig ?? []" class="w-full md:w-1/2 h-64 mb-4" />
    </div>
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b" style="border-color: rgb(var(--border-default));">
                    <th class="py-2 pr-4">Title</th>
                    <th class="py-2 pr-4">Project</th>
                    <th class="py-2 pr-4">Assignee</th>
                    <th class="py-2 pr-4">Due</th>
                    <th class="py-2 pr-4">Completed</th>
                    <th class="py-2 pr-4 text-right">On-Time</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                @forelse ($rows as $r)
                    <tr>
                        <td class="py-2 pr-4">{{ $r->title ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->project_name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->assignee_name ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $fmt($r->due_date ?? null) }}</td>
                        <td class="py-2 pr-4">{{ $fmt($r->completed_at ?? null) }}</td>
                        <td class="py-2 pr-4 text-right">
                            <span class="chip {{ $r->on_time ?? 0 ? 'chip--status-active' : 'chip--muted' }}">
                                {{ $r->on_time ?? 0 ? 'On time' : 'Late' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-text-subtle">No tasks in range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $rows->links() }}</div>
    </div>
@endsection
