@extends('layouts.app')
@section('title', 'Email Activity')

@section('content')
    @php $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id); @endphp

    <x-reports.back-button :tenant="$tenant" />

    <div class="oh-card mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">Email Activity</h1>
                <p class="text-sm text-text-subtle">Outbound: {{ $summary['outbound'] ?? 0 }} • Inbound:
                    {{ $summary['inbound'] ?? 0 }}</p>
            </div>
            <form method="GET" class="flex gap-2"
                action="{{ route('tenant.admin.reports.emails.activity', ['tenant' => $tenantParam]) }}">
                <select name="range" class="oh-select">
                    @foreach (['wtd' => 'WTD', 'mtd' => 'MTD', 'qtd' => 'QTD', 'ytd' => 'YTD', 'last30' => 'Last 30'] as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['range'] ?? 'last30') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="oh-btn bg-brand-primary text-white">Apply</button>
            </form>
        </div>
    </div>

    {{-- Simple bar rows --}}
    <div class="oh-card mb-4">
        <ul class="grid gap-2">
            @php
                $max = max(1, $series->max(fn($r) => max($r->out_cnt, $r->in_cnt)));
            @endphp
            @forelse ($series as $s)
                <li class="flex items-center gap-3">
                    <span class="w-24 text-xs text-text-subtle">{{ \Carbon\Carbon::parse($s->d)->format('M d') }}</span>
                    <div class="flex-1 h-2 bg-surface-accent rounded relative">
                        <span class="absolute left-0 top-0 h-2 rounded bg-blue-600/80"
                            style="width: {{ (int) round(($s->out_cnt / $max) * 100) }}%"></span>
                    </div>
                    <span class="w-10 text-right text-xs">Out {{ $s->out_cnt }}</span>

                    <div class="flex-1 h-2 bg-surface-accent rounded relative">
                        <span class="absolute left-0 top-0 h-2 rounded bg-green-600/80"
                            style="width: {{ (int) round(($s->in_cnt / $max) * 100) }}%"></span>
                    </div>
                    <span class="w-10 text-right text-xs">In {{ $s->in_cnt }}</span>
                </li>
            @empty
                <li class="text-text-subtle text-sm">No email activity in range.</li>
            @endforelse
        </ul>
    </div>
    {{-- TEMP: bypass component --}}
    <div class="oh-card mb-4">
        <x-oh-chart :config="$chartConfig" class="w-full h-72 mb-4" />

    </div>


    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b">
                    <th class="py-2 pr-4">When</th>
                    <th class="py-2 pr-4">Dir</th>
                    <th class="py-2 pr-4">Subject</th>
                    <th class="py-2 pr-4">From</th>
                    <th class="py-2 pr-4">To</th>
                    <th class="py-2 pr-4">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($rows as $r)
                    <tr>
                        <td class="py-2 pr-4">{{ \Carbon\Carbon::parse($r->ts)->format('M d, Y h:ia') }}</td>
                        <td class="py-2 pr-4"><span
                                class="chip {{ $r->direction === 'outbound' ? 'chip--status-active' : 'chip--muted' }}">{{ $r->direction }}</span>
                        </td>
                        <td class="py-2 pr-4">{{ $r->subject ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->from_email ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->recipient_email ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->status ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-text-subtle">No emails found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $rows->links() }}</div>
    </div>

@endsection
