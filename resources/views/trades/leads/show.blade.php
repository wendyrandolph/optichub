@extends('layouts.trades')

@section('title', 'Lead Details')

@section('trades-content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $lead->name }}</h1>
                <p class="text-sm text-text-subtle mt-1">Follow up and convert to a job when ready.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (!$lead->first_contacted_at)
                    <form method="POST" action="{{ route('tenant.trades.leads.contact', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}">
                        @csrf
                        <button type="submit" class="oh-btn">Mark contacted</button>
                    </form>
                @endif
                <a href="{{ route('tenant.trades.leads.edit', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="oh-btn">Edit</a>
                <form method="POST" action="{{ route('tenant.trades.leads.convert', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}">
                    @csrf
                    <button type="submit" class="oh-btn oh-btn--primary">Convert to client</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="oh-card p-5 lg:col-span-2 space-y-4">
                <div>
                    <div class="text-xs text-text-subtle uppercase tracking-wide">Contact</div>
                    <div class="text-sm text-text-base mt-2">
                        {{ $lead->email ?? 'No email' }}
                        @if ($lead->phone)
                            · {{ $lead->phone }}
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-text-subtle">
                    <div>
                        <div class="text-xs uppercase tracking-wide">Status</div>
                        <div class="text-text-base font-medium mt-1">{{ ucfirst($lead->status ?? 'new') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide">Source</div>
                        <div class="text-text-base font-medium mt-1">{{ ucfirst($lead->source ?? 'manual') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide">Assigned</div>
                        <div class="text-text-base font-medium mt-1">
                            {{ $lead->assignedTo?->first_name ? trim(($lead->assignedTo->first_name ?? '') . ' ' . ($lead->assignedTo->last_name ?? '')) : 'Unassigned' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide">Response time</div>
                        <div class="text-text-base font-medium mt-1">
                            @if ($lead->first_contacted_at)
                                {{ $lead->created_at?->diffForHumans($lead->first_contacted_at, [
                                    'parts' => 2,
                                    'short' => true,
                                    'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                                ]) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-text-subtle">Preferred time</div>
                    <div class="text-sm text-text-base mt-1">{{ $lead->preferred_time ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-text-subtle">Service address</div>
                    <div class="text-sm text-text-base mt-1">{{ $lead->service_address ?? '—' }}</div>
                </div>
            </div>

            <div class="oh-card p-5 space-y-4">
                <div class="text-xs uppercase tracking-wide text-text-subtle">Actions</div>
                <form method="POST" action="{{ route('tenant.trades.leads.status', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="space-y-2">
                    @csrf
                    <label class="text-xs text-text-subtle uppercase tracking-wide">Change status</label>
                    <div class="flex gap-2">
                        <select name="status" class="oh-select h-9 flex-1">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected($lead->status === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="oh-btn">Update</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('tenant.trades.leads.assign', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="space-y-2">
                    @csrf
                    <label class="text-xs text-text-subtle uppercase tracking-wide">Assign lead</label>
                    <div class="flex gap-2">
                        <select name="assigned_to_user_id" class="oh-select h-9 flex-1">
                            <option value="">Unassigned</option>
                            @foreach ($assignees as $assignee)
                                @php
                                    $assigneeName = trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? '')) ?: $assignee->email;
                                @endphp
                                <option value="{{ $assignee->id }}" @selected((string) $lead->assigned_to_user_id === (string) $assignee->id)>
                                    {{ $assigneeName }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="oh-btn">Assign</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="oh-card p-5">
            <div class="text-xs uppercase tracking-wide text-text-subtle">Description</div>
            <p class="text-sm text-text-base mt-2">{{ $lead->description ?? 'No description provided.' }}</p>
        </div>

        @php
            $customFields = data_get($lead->source_detail ?? [], 'custom_fields', []);
            $extraFields = data_get($lead->source_detail ?? [], 'extra_fields', []);
        @endphp
        @if (!empty($customFields) || !empty($extraFields))
            <div class="oh-card p-5 space-y-3">
                <div class="text-xs uppercase tracking-wide text-text-subtle">Additional details</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    @foreach ($customFields as $field)
                        <div>
                            <div class="text-xs uppercase tracking-wide text-text-subtle">{{ $field['label'] ?? 'Field' }}</div>
                            <div class="text-text-base mt-1">{{ $field['value'] ?? '—' }}</div>
                        </div>
                    @endforeach
                    @foreach ($extraFields as $label => $value)
                        <div>
                            <div class="text-xs uppercase tracking-wide text-text-subtle">{{ $label }}</div>
                            <div class="text-text-base mt-1">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="oh-card p-5 lg:col-span-2 space-y-3">
                <div class="text-xs uppercase tracking-wide text-text-subtle">Timeline</div>
                @if ($events->isEmpty())
                    <p class="text-sm text-text-subtle">No activity yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($events as $event)
                            <div class="flex items-start gap-3">
                                <div class="mt-1 h-2 w-2 rounded-full bg-[rgb(var(--brand-primary))]"></div>
                                <div class="min-w-0">
                                    <div class="text-sm text-text-base">
                                        {{ ucfirst(str_replace('_', ' ', $event->type)) }}
                                        @if (!empty($event->payload_json['note']))
                                            <div class="text-xs text-text-subtle mt-1">{{ $event->payload_json['note'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-xs text-text-subtle">{{ $event->created_at?->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="oh-card p-5 space-y-3">
                <div class="text-xs uppercase tracking-wide text-text-subtle">Notes</div>
                <p class="text-sm text-text-base">{{ $lead->notes ?? 'No notes yet.' }}</p>
                <form method="POST" action="{{ route('tenant.trades.leads.note', ['tenant' => $tenant->id, 'lead' => $lead->id]) }}" class="space-y-2">
                    @csrf
                    <textarea name="note" class="oh-input min-h-[90px]" rows="3" placeholder="Add a note..."></textarea>
                    <button type="submit" class="oh-btn">Add note</button>
                </form>
            </div>
        </div>
    </div>
@endsection
