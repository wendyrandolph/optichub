@extends('layouts.app')

@section('title', 'Recurring Invoices')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Billing</p>
                <h1 class="text-2xl font-semibold text-text-base">Recurring Invoices</h1>
                <p class="mt-1 text-sm text-text-subtle">Automatically create invoices on a schedule.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.invoices.index', ['tenant' => $tenantId]) }}" class="oh-btn">Back</a>
                <a href="{{ route('tenant.invoices.recurring.create', ['tenant' => $tenantId]) }}" class="oh-btn oh-btn--primary">
                    <i class="fa-solid fa-plus text-[12px] mr-2"></i> New recurring invoice
                </a>
            </div>
        </div>

        <div class="oh-card border border-border-default/70 shadow-card overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                    <tr class="text-left">
                        <th class="px-4 py-3 font-medium">Client</th>
                        <th class="px-4 py-3 font-medium">Frequency</th>
                        <th class="px-4 py-3 font-medium">Next run</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border)/.35);">
                    @forelse ($recurring as $rec)
                        <tr>
                            <td class="px-4 py-3">
                                {{ trim(($rec->client->firstName ?? '') . ' ' . ($rec->client->lastName ?? '')) ?: 'Client #' . $rec->contact_id }}
                            </td>
                            <td class="px-4 py-3">
                                {{ ucfirst($rec->frequency) }} · every {{ $rec->interval }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $rec->next_run_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="oh-pill {{ $rec->status === 'active' ? 'oh-pill--success' : 'oh-pill--muted' }}">
                                    {{ ucfirst($rec->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                    action="{{ route('tenant.invoices.recurring.toggle', ['tenant' => $tenantId, 'recurring' => $rec->id]) }}"
                                    class="inline">
                                    @csrf
                                    <button class="oh-btn">
                                        {{ $rec->status === 'active' ? 'Pause' : 'Resume' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-text-subtle">
                                No recurring invoices yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
