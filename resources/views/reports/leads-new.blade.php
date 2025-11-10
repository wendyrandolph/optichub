@extends('layouts.app')
@section('title', 'New Leads')

@section('content')
    @php $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id); @endphp


    {{-- Back to Reports --}}
    <x-reports.back-button :tenant="$tenant" />


    <div class="oh-card mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold">New Leads</h1>
                <p class="text-sm text-text-subtle">{{ $summary['count'] ?? 0 }} leads in range</p>
            </div>
            <form class="flex gap-2" method="GET"
                action="{{ route('tenant.admin.reports.leads.new', ['tenant' => $tenantParam]) }}">
                <select name="range" class="oh-select">
                    @foreach (['wtd' => 'WTD', 'mtd' => 'MTD', 'qtd' => 'QTD', 'ytd' => 'YTD', 'last30' => 'Last 30'] as $k => $v)
                        <option value="{{ $k }}" @selected(($filters['range'] ?? 'wtd') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <input name="source" class="oh-select" placeholder="Source" value="{{ $filters['source'] ?? '' }}">
                <input name="status" class="oh-select" placeholder="Status" value="{{ $filters['status'] ?? '' }}">
                <input name="q" class="oh-select" placeholder="Search" value="{{ $filters['qText'] ?? '' }}">
                <button class="oh-btn bg-brand-primary text-white">Apply</button>
            </form>
        </div>
    </div>
    <div class="oh-card mb-4">
        <x-oh-chart :config="$leadConfig ?? []" class="w-full h-64 mb-4" />
    </div>
    <div class="oh-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-text-subtle">
                <tr class="border-b">
                    <th class="py-2 pr-4">Name</th>
                    <th class="py-2 pr-4">Email</th>
                    <th class="py-2 pr-4">Source</th>
                    <th class="py-2 pr-4">Status</th>
                    <th class="py-2 pr-4">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($rows as $r)
                    <tr>
                        <td class="py-2 pr-4">
                            {{ trim($r->first_name . ' ' . $r->last_name) ?: '—' }}
                        </td>
                        <td class="py-2 pr-4">{{ $r->email ?? '—' }}</td>
                        <td class="py-2 pr-4">{{ $r->source ?? '—' }}</td>
                        <td class="py-2 pr-4"><span class="chip chip--muted">{{ $r->status ?? '—' }}</span></td>
                        <td class="py-2 pr-4">{{ \Carbon\Carbon::parse($r->created_at ?? now())->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-text-subtle">No leads found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $rows->links() }}</div>
    </div>
@endsection
