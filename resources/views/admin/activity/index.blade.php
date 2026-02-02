@extends('layouts.app')
@section('title', 'Activity')

@section('content')
    @php
        $u = auth()->user();
        // ensure we always have an iterable
        $rows = isset($recentActivity) ? $recentActivity : collect();
    @endphp

    <div class="oh-page space-y-6">
        <header class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-text-subtle">Operations</div>
                <h1 class="text-2xl font-semibold text-text-base mt-1">Recent Activity</h1>
                <p class="text-sm text-text-subtle mt-1">Audit actions across your workspace.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="oh-btn">
                    <i class="fa-solid fa-chart-simple mr-2" aria-hidden="true"></i>
                    Dashboard
                </a>
                <a href="{{ url('admin/reports') }}" class="oh-btn">
                    <i class="fa-solid fa-chart-line mr-2" aria-hidden="true"></i>
                    Reports
                </a>
            </div>
        </header>

        <section class="oh-card space-y-3">
            <form method="GET" action="{{ route('admin.activity.index') }}"
                class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="w-full md:max-w-[220px]">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-text-subtle">From</label>
                    <input type="date" name="from" value="{{ $from ?? '' }}" class="oh-input mt-1 w-full">
                </div>
                <div class="w-full md:max-w-[220px]">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-text-subtle">To</label>
                    <input type="date" name="to" value="{{ $to ?? '' }}" class="oh-input mt-1 w-full">
                </div>
                <div class="flex items-center gap-2 md:pt-6">
                    <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                    @if (!empty($actionFilter) || !empty($from) || !empty($to))
                        <a href="{{ route('admin.activity.index') }}" class="oh-btn">Reset</a>
                    @endif
                </div>
            </form>
            <div class="flex flex-wrap gap-2">
                @php
                    $allActive = empty($actionFilter);
                    $pillClass = fn($active) => $active ? 'oh-pill oh-pill--brand' : 'oh-pill oh-pill--muted';
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['action' => null, 'page' => null]) }}"
                    class="{{ $pillClass($allActive) }}" @if ($allActive) aria-current="page" @endif>
                    All Actions
                </a>
                @foreach ($actionOptions ?? [] as $action)
                    @php
                        $isActive = ($actionFilter ?? '') === $action;
                        $actionLabel = ucwords(str_replace(['_', '.'], ' ', strtolower($action)));
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['action' => $action, 'page' => null]) }}"
                        class="{{ $pillClass($isActive) }}" @if ($isActive) aria-current="page" @endif>
                        {{ $actionLabel }}
                    </a>
                @endforeach
            </div>
        </section>

        @if (
            (is_object($rows) && method_exists($rows, 'count') && $rows->count() === 0) ||
                (is_array($rows) && count($rows) === 0))
            <p class="text-text-subtle">No activity yet.</p>
        @else
            <div class="oh-card p-0 overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-surface-muted/60 text-left text-text-subtle">
                            <th class="py-2 px-3">When</th>
                            <th class="py-2 px-3">Action</th>
                            <th class="py-2 px-3">Subject</th>
                            <th class="py-2 px-3">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $a)
                            @php
                                $subjectType = class_basename($a->related_type ?? '');
                                $subjectName =
                                    $a->related->name ?? ($a->related->title ?? '#' . ($a->related_id ?? '—'));
                                $actor = optional($a->user)->first_name
                                    ? trim(($a->user->first_name ?? '') . ' ' . ($a->user->last_name ?? ''))
                                    : $a->user->email ?? 'System';
                                $typeSlug = strtolower($subjectType);
                                $created = optional($a->created_at)->diffForHumans() ?? '—';
                            @endphp
                            <tr class="border-b border-border-default/60">
                                <td class="py-2 px-3 whitespace-nowrap">{{ $created }}</td>
                                <td class="py-2 px-3">
                                    @php
                                        $rawAction = $a->action ?? 'event';
                                        $actionLabel = ucwords(str_replace(['_', '.'], ' ', strtolower($rawAction)));
                                    @endphp
                                    <span class="font-medium text-text-base">{{ $actionLabel }}</span>
                                    @if (!empty($a->description))
                                        <span class="ml-2 text-text-subtle">— {{ $a->description }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">
                                    <span class="text-text-subtle">{{ $subjectType ?: 'Record' }}</span>:
                                    @if (in_array($typeSlug, ['project', 'client', 'task']) &&
                                            \Illuminate\Support\Facades\Route::has('admin.activity.related'))
                                        <a class="text-brand-primary hover:underline"
                                            href="{{ route('admin.activity.related', ['type' => $typeSlug, 'id' => $a->related_id]) }}">
                                            {{ $subjectName }}
                                        </a>
                                    @else
                                        <span class="text-text-base">{{ $subjectName }}</span>
                                    @endif
                                </td>
                                <td class="py-2 px-3">{{ $actor }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (is_object($rows) && method_exists($rows, 'links'))
                <div class="mt-4">
                    {{ $rows->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
