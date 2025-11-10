@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
    @php
        $tenantParam = $tenant->getKey();
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-text-base">Lead Details</h1>
            <div class="flex gap-2">
                <a href="{{ route('tenant.leads.edit', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}"
                    class="px-3 py-2 rounded-lg text-sm bg-surface-card/70 hover:bg-surface-card/90">
                    Edit
                </a>
                <form method="POST" action="{{ route('tenant.leads.destroy', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}"
                    onsubmit="return confirm('Delete this lead?');">
                    @csrf @method('DELETE')
                    <button class="px-3 py-2 rounded-lg text-sm bg-rose-50 text-rose-600 hover:bg-rose-100">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="rounded-2xl border border-border-default/60 bg-surface-card/70 p-6 space-y-4">
            <div>
                <div class="text-sm text-text-subtle">Name</div>
                <div class="text-lg font-medium text-text-base">{{ $lead->name }}</div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-text-subtle">Email</div>
                    <div class="text-text-base">{{ $lead->email ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-sm text-text-subtle">Phone</div>
                    <div class="text-text-base">{{ $lead->phone ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-sm text-text-subtle">Status</div>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-[11px] border">
                        {{ ucfirst($lead->status) }}
                    </span>
                </div>
                <div>
                    <div class="text-sm text-text-subtle">Source</div>
                    <div class="text-text-base">{{ $lead->source ?: '—' }}</div>
                </div>
            </div>

            @if ($lead->notes)
                <div>
                    <div class="text-sm text-text-subtle">Notes</div>
                    <p class="text-text-base whitespace-pre-line">{{ $lead->notes }}</p>
                </div>
            @endif

            <div class="text-xs text-text-subtle">
                Added {{ optional($lead->created_at)->format('M j, Y') }} • Updated
                {{ optional($lead->updated_at)->diffForHumans() }}
            </div>
        </div>

        <div>
            <a href="{{ route('tenant.leads.index', ['tenant' => $tenantParam]) }}"
                class="inline-flex items-center gap-2 text-sm text-blue-700 hover:underline">
                ← Back to Leads
            </a>
        </div>
    </div>
@endsection
