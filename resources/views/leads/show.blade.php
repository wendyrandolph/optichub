@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
    @php
        $tenantParam = $tenant->getKey();
        $statusPill = function ($status) {
            $s = strtolower((string) $status);
            return match (true) {
                str_contains($s, 'new') => 'oh-pill oh-pill--brand',
                str_contains($s, 'contact') => 'oh-pill oh-pill--info',
                str_contains($s, 'qualified') => 'oh-pill oh-pill--accent',
                str_contains($s, 'unqualified') => 'oh-pill oh-pill--muted',
                str_contains($s, 'converted') => 'oh-pill oh-pill--success',
                str_contains($s, 'archived') => 'oh-pill oh-pill--muted',
                default => 'oh-pill',
            };
        };
        $submittedAt = $lead->submitted_at ?? $lead->created_at;
        $owner = $lead->ownerUser ?? $lead->owner;
        $ownerName = $owner ? (trim(($owner->first_name ?? '') . ' ' . ($owner->last_name ?? '')) ?: $owner->email) : 'Unassigned';
    @endphp

    <div class="oh-page max-w-5xl space-y-6">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-[0.08em] text-text-subtle">Leads</div>
                <h1 class="text-2xl font-semibold text-text-base mt-1">
                    {{ $lead->name ?: 'Lead Details' }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="{{ $statusPill($lead->status) }}">{{ ucfirst($lead->status) }}</span>
                    <span class="text-xs text-text-subtle">Owner: {{ $ownerName }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.leads.index', ['tenant' => $tenantParam]) }}"
                    class="oh-btn inline-flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Back to Leads
                </a>
                <a href="{{ route('tenant.leads.edit', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}"
                    class="oh-btn">Edit</a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <div class="oh-card p-5 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('tenant.leads.update', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="name" value="{{ $lead->name }}">
                            <input type="hidden" name="status" value="contacted">
                            <button type="submit" class="oh-btn">Mark contacted</button>
                        </form>
                        <form method="POST" action="{{ route('tenant.leads.update', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="name" value="{{ $lead->name }}">
                            <input type="hidden" name="status" value="archived">
                            <button type="submit" class="oh-btn">Archive</button>
                        </form>
                        <form method="POST" action="{{ route('tenant.leads.convert', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--primary">Convert</button>
                        </form>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-text-subtle">Email</div>
                            <div class="text-text-base">{{ $lead->email ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Phone</div>
                            <div class="text-text-base">{{ $lead->phone_formatted ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Company</div>
                            <div class="text-text-base">{{ $lead->company ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Priority</div>
                            <div class="text-text-base">{{ ucfirst($lead->priority ?? 'normal') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Source</div>
                            <div class="text-text-base">{{ $lead->source ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Submitted</div>
                            <div class="text-text-base">{{ $submittedAt ? $submittedAt->format('M j, Y g:i A') : '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Form name</div>
                            <div class="text-text-base">{{ $lead->form_name ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-text-subtle">Source URL</div>
                            <div class="text-text-base break-all">{{ $lead->source_url ?: '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="oh-card p-5">
                    <div class="flex items-center gap-2 text-xs text-text-subtle">
                        <button type="button" class="oh-btn" data-tab-btn="details">Details</button>
                        <button type="button" class="oh-btn" data-tab-btn="timeline">Timeline</button>
                        <button type="button" class="oh-btn" data-tab-btn="notes">Notes</button>
                    </div>

                    <div class="mt-4 space-y-4" data-tab="details">
                        <div>
                            <div class="text-sm text-text-subtle">Message</div>
                            <p class="text-text-base whitespace-pre-line">{{ $lead->message ?: '—' }}</p>
                        </div>
                        @if ($lead->notes)
                            <div>
                                <div class="text-sm text-text-subtle">Internal notes</div>
                                <p class="text-text-base whitespace-pre-line">{{ $lead->notes }}</p>
                            </div>
                        @endif
                        @if (!empty($lead->meta))
                            <div>
                                <div class="text-sm text-text-subtle">Additional fields</div>
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                    @foreach ($lead->meta as $key => $value)
                                        <div class="rounded-lg border border-border-default/60 px-3 py-2">
                                            <div class="text-[11px] uppercase tracking-wide text-text-subtle">{{ $key }}</div>
                                            <div class="text-text-base break-words">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class="grid sm:grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-text-subtle">UTM Source</div>
                                <div>{{ $lead->utm_source ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-text-subtle">UTM Medium</div>
                                <div>{{ $lead->utm_medium ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-text-subtle">UTM Campaign</div>
                                <div>{{ $lead->utm_campaign ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-text-subtle">UTM Term</div>
                                <div>{{ $lead->utm_term ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-text-subtle">UTM Content</div>
                                <div>{{ $lead->utm_content ?: '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 hidden" data-tab="timeline">
                        @forelse ($events as $event)
                            <div class="rounded-lg border border-border-default/60 px-4 py-3">
                                <div class="text-xs text-text-subtle uppercase tracking-wide">{{ str_replace('_', ' ', $event->type) }}</div>
                                <div class="text-sm text-text-base mt-1">
                                    @if ($event->type === 'note')
                                        {{ data_get($event->payload, 'note') }}
                                    @elseif ($event->type === 'status_change')
                                        Status changed from {{ data_get($event->payload, 'from') ?: '—' }} to {{ data_get($event->payload, 'to') ?: '—' }}
                                    @elseif ($event->type === 'assignment')
                                        Assignment updated.
                                    @elseif ($event->type === 'conversion')
                                        Lead converted.
                                    @else
                                        Update recorded.
                                    @endif
                                </div>
                                <div class="text-[11px] text-text-subtle mt-2">{{ $event->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="text-sm text-text-subtle">No timeline events yet.</div>
                        @endforelse
                    </div>

                    <div class="mt-4 space-y-4 hidden" data-tab="notes">
                        <form method="POST" action="{{ route('tenant.leads.notes.store', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}" class="space-y-2">
                            @csrf
                            <textarea name="note" rows="3" class="oh-input w-full" placeholder="Add a note..."></textarea>
                            <button type="submit" class="oh-btn oh-btn--primary">Add note</button>
                        </form>
                        <div class="space-y-3">
                            @forelse ($events->where('type', 'note') as $note)
                                <div class="rounded-lg border border-border-default/60 px-4 py-3">
                                    <div class="text-sm text-text-base">{{ data_get($note->payload, 'note') }}</div>
                                    <div class="text-[11px] text-text-subtle mt-2">{{ $note->created_at->diffForHumans() }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-text-subtle">No notes yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="oh-card p-5 space-y-3">
                    <div class="text-sm font-semibold text-text-base">Assign owner</div>
                    <form method="POST" action="{{ route('tenant.leads.update', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="name" value="{{ $lead->name }}">
                        <input type="hidden" name="status" value="{{ $lead->status }}">
                        <select name="owner_user_id" class="oh-select w-full">
                            <option value="">Unassigned</option>
                            @foreach ($owners as $o)
                                @php
                                    $label = trim(($o->first_name ?? '') . ' ' . ($o->last_name ?? '')) ?: ($o->email ?? 'Owner');
                                @endphp
                                <option value="{{ $o->id }}" @selected((string) $lead->owner_user_id === (string) $o->id)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="oh-btn oh-btn--primary w-full">Save owner</button>
                    </form>
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="text-sm font-semibold text-text-base">Convert lead</div>
                    <form method="POST" action="{{ route('tenant.leads.convert', ['tenant' => $tenantParam, 'lead' => $lead->id]) }}" class="space-y-3">
                        @csrf
                        <label class="flex items-center gap-2 text-sm text-text-subtle">
                            <input type="checkbox" name="create_opportunity" value="1" class="rounded border-border-default text-brand-primary" checked>
                            <span>Create opportunity</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-subtle">
                            <input type="checkbox" name="create_project" value="1" class="rounded border-border-default text-brand-primary">
                            <span>Create project</span>
                        </label>
                        <input type="text" name="opportunity_title" class="oh-input h-9" placeholder="Opportunity title (optional)">
                        <input type="text" name="project_title" class="oh-input h-9" placeholder="Project title (optional)">
                        <button type="submit" class="oh-btn oh-btn--primary w-full">Convert to contact</button>
                    </form>
                </div>
            </aside>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const buttons = document.querySelectorAll('[data-tab-btn]');
                const panes = document.querySelectorAll('[data-tab]');
                if (!buttons.length) return;
                const showTab = (name) => {
                    panes.forEach((pane) => {
                        pane.classList.toggle('hidden', pane.dataset.tab !== name);
                    });
                };
                buttons.forEach((button) => {
                    button.addEventListener('click', () => showTab(button.dataset.tabBtn));
                });
                showTab('details');
            });
        </script>
    @endpush
@endsection
