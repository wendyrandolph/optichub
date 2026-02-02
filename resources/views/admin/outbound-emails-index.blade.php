@extends('layouts.admin')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Outbound email log</h1>
                <p class="text-sm text-text-subtle">Queued, sent, and failed transactional emails.</p>
            </div>
        </div>

        <form class="oh-card p-4 grid gap-4 sm:grid-cols-4 items-end" method="get">
            <div>
                <label class="oh-label" for="status">Status</label>
                <select id="status" name="status" class="oh-select w-full">
                    <option value="">All</option>
                    @foreach (['queued', 'sent', 'failed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="oh-label" for="tenant_id">Tenant ID</label>
                <input id="tenant_id" name="tenant_id" type="number" class="oh-input w-full"
                    value="{{ request('tenant_id') }}">
            </div>
            <div>
                <label class="oh-label" for="from">From</label>
                <input id="from" name="from" type="date" class="oh-input w-full" value="{{ request('from') }}">
            </div>
            <div>
                <label class="oh-label" for="to">To</label>
                <input id="to" name="to" type="date" class="oh-input w-full" value="{{ request('to') }}">
            </div>
            <div class="sm:col-span-4 flex justify-end gap-2">
                <a href="{{ route('admin.outbound-emails.index') }}" class="oh-btn oh-btn--ghost">Reset</a>
                <button class="oh-btn oh-btn--primary" type="submit">Apply</button>
            </div>
        </form>

        <div class="oh-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[rgba(var(--ui-border),0.15)] text-text-subtle">
                        <tr>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">To</th>
                            <th class="text-left px-4 py-3">Subject</th>
                            <th class="text-left px-4 py-3">Tenant</th>
                            <th class="text-left px-4 py-3">Created</th>
                            <th class="text-right px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(var(--ui-border),0.2)]">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="oh-pill">{{ ucfirst($log->status) }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $log->to_email }}</td>
                                <td class="px-4 py-3">{{ $log->subject ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $log->tenant_id ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($log->status === 'failed')
                                        <form method="post"
                                            action="{{ route('admin.outbound-emails.retry', $log) }}">
                                            @csrf
                                            <button class="oh-btn oh-btn--ghost" type="submit">Retry</button>
                                        </form>
                                    @else
                                        <span class="text-text-subtle">—</span>
                                    @endif
                                </td>
                            </tr>
                            @if ($log->error)
                                <tr>
                                    <td colspan="6" class="px-4 pb-4 text-xs text-rose-600">
                                        {{ $log->error }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-text-subtle">
                                    No outbound emails found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $logs->links() }}
    </div>
@endsection
