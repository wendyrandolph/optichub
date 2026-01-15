@extends('layouts.app')
@section('title', 'WIP / Workload')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $taskRows = $taskRows ?? collect();
        $projectRows = $projectRows ?? collect();
        $taskQueryBase = ['status' => 'wip'];
        $projectQueryBase = ['status' => 'open'];
    @endphp

    <x-reports.back-button :tenant="$tenant" />

    <div class="oh-card mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-text-base">WIP / Workload</h1>
                <p class="text-sm text-text-subtle">Open tasks per assignee and active projects per owner.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="oh-card">
            <h2 class="text-sm font-semibold text-text-base mb-2">Open Tasks by Assignee</h2>
            <x-oh-chart :config="$taskConfig" class="w-full h-64" />
        </div>
        <div class="oh-card">
            <h2 class="text-sm font-semibold text-text-base mb-2">Active Projects by Owner</h2>
            <x-oh-chart :config="$projectConfig" class="w-full h-64" />
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 mt-4">
        <div class="oh-card">
            <h3 class="text-sm font-semibold text-text-base mb-3">Task WIP Table</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-text-subtle">
                        <tr class="border-b" style="border-color: rgb(var(--border-default));">
                            <th class="py-2 pr-4">Assignee</th>
                            <th class="py-2 pr-2 text-right">Open Tasks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                        @forelse ($taskRows as $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    @php
                                        $taskQuery = $taskQueryBase;
                                        if (!empty($row->user_id)) {
                                            $taskQuery['assignee_id'] = $row->user_id;
                                        }
                                    @endphp
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.tasks.index', array_merge(['tenant' => $tenantParam], $taskQuery)) }}">
                                        {{ $row->name }}
                                    </a>
                                </td>
                                <td class="py-2 pr-2 text-right">
                                    @php
                                        $taskQuery = $taskQueryBase;
                                        if (!empty($row->user_id)) {
                                            $taskQuery['assignee_id'] = $row->user_id;
                                        }
                                    @endphp
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.tasks.index', array_merge(['tenant' => $tenantParam], $taskQuery)) }}">
                                        {{ (int) $row->cnt }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-text-subtle">No open tasks yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="oh-card">
            <h3 class="text-sm font-semibold text-text-base mb-3">Project WIP Table</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-text-subtle">
                        <tr class="border-b" style="border-color: rgb(var(--border-default));">
                            <th class="py-2 pr-4">Owner</th>
                            <th class="py-2 pr-2 text-right">Active Projects</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="divide-color: rgb(var(--border-default));">
                        @forelse ($projectRows as $row)
                            <tr>
                                <td class="py-2 pr-4">
                                    @php
                                        $projectQuery = $projectQueryBase;
                                        if (!empty($row->owner_id)) {
                                            $projectQuery['owner_id'] = $row->owner_id;
                                        }
                                    @endphp
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.projects.index', array_merge(['tenant' => $tenantParam], $projectQuery)) }}">
                                        {{ $row->name }}
                                    </a>
                                </td>
                                <td class="py-2 pr-2 text-right">
                                    @php
                                        $projectQuery = $projectQueryBase;
                                        if (!empty($row->owner_id)) {
                                            $projectQuery['owner_id'] = $row->owner_id;
                                        }
                                    @endphp
                                    <a class="text-text-base hover:underline"
                                       href="{{ route('tenant.projects.index', array_merge(['tenant' => $tenantParam], $projectQuery)) }}">
                                        {{ (int) $row->cnt }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-text-subtle">No active projects yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
