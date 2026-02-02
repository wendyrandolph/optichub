@extends('layouts.app')

@section('title', 'Opportunity')

@section('content')
    @php
        $tenantId = $tenant->id ?? ($tenant ?? request()->route('tenant'));
        $tenantId = $tenantId instanceof \App\Models\Tenant ? $tenantId->id : (int) $tenantId;
        $opp = $opportunity;
        $overdue = $opp->next_followup_at && !in_array(strtolower($opp->stage), ['won', 'lost']) && $opp->next_followup_at->isPast();
        $pillClass = function ($stage) {
            return match (strtolower($stage)) {
                'new' => 'oh-pill oh-pill--info',
                'qualified' => 'oh-pill oh-pill--muted',
                'proposal' => 'oh-pill oh-pill--warning',
                'negotiation' => 'oh-pill oh-pill--warning',
                'won' => 'oh-pill oh-pill--success',
                'lost' => 'oh-pill oh-pill--danger',
                default => 'oh-pill',
            };
        };
    @endphp
    <div class="oh-page max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Pipeline</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $opp->title }}</h1>
                <p class="text-sm text-text-subtle">Review the opportunity details and keep follow-ups on track.</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('tenant.opportunities.index', ['tenant' => $tenantId]) }}" class="oh-btn">
                    <i class="fa-solid fa-arrow-left mr-2 text-xs" aria-hidden="true"></i>
                    View All Opportunities
                </a>
                @if (strtolower($opp->stage) === 'won')
                    <form method="POST"
                        action="{{ route('tenant.opportunities.convert', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}">
                        @csrf
                        <button type="submit" class="oh-btn oh-btn--primary">Convert to Project</button>
                    </form>
                @endif
                @if (strtolower($opp->stage) === 'lost')
                    <form method="POST"
                        action="{{ route('tenant.opportunities.destroy', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                        onsubmit="return confirm('Delete this lost opportunity?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-btn">
                            <i class="fa-solid fa-trash mr-2 text-xs" aria-hidden="true"></i>
                            Delete
                        </button>
                    </form>
                @endif
                <a href="{{ route('tenant.opportunities.edit', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                    class="oh-btn">Edit</a>
            </div>
        </header>

        <section class="oh-card p-5 space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <span class="{{ $pillClass($opp->stage) }}">{{ ucfirst($opp->stage) }}</span>
                @if ($overdue)
                    <span class="oh-pill oh-pill--danger text-[11px]">Overdue follow-up</span>
                @endif
            </div>
            <p class="text-xs text-text-subtle">Convert to Project is available once the opportunity is marked Won.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Estimated value</div>
                    <div class="text-text-base">{{ '$' . number_format((float) $opp->estimated_value, 0) }}</div>
                </div>
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Expected close</div>
                    <div class="text-text-base">{{ $opp->expected_close_date ? $opp->expected_close_date->format('M j, Y') : '—' }}</div>
                </div>
                @php
                    $ownerName = $opp->owner
                        ? trim(($opp->owner->first_name ?? '') . ' ' . ($opp->owner->last_name ?? '')) ?: ($opp->owner->email ?? 'Unassigned')
                        : 'Unassigned';
                @endphp
                <div>
                    <div class="text-text-subtle text-xs uppercase tracking-wide">Owner</div>
                    <div class="text-text-base">{{ $ownerName }}</div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            <div class="xl:col-span-2 space-y-4">
                <section class="oh-card p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-text-base">Details</h2>
                            <p class="text-xs text-text-subtle mt-1">Key information tied to this opportunity.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-text-subtle text-xs uppercase tracking-wide">Company</div>
                            <div class="text-text-base">{{ $opp->company->company_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-text-subtle text-xs uppercase tracking-wide">Lead</div>
                            <div class="text-text-base">{{ $opp->lead->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-text-subtle text-xs uppercase tracking-wide">Next step</div>
                            <div class="text-text-base">{{ $opp->next_step ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-text-subtle text-xs uppercase tracking-wide">Next follow-up</div>
                            <div class="text-text-base">
                                {{ $opp->next_followup_at ? $opp->next_followup_at->format('M j, Y g:ia') : '—' }}
                            </div>
                        </div>
                    </div>
                    @if ($opp->notes)
                        <div class="pt-3 border-t border-border-default/60">
                            <div class="text-text-subtle text-xs uppercase tracking-wide mb-1">Notes</div>
                            <p class="text-sm text-text-base whitespace-pre-line">{{ $opp->notes }}</p>
                        </div>
                    @endif
                </section>
            </div>

            <div class="md:grid md:grid-cols-2 md:gap-4 min-[1220px]:block min-[1220px]:space-y-4">
                <section class="oh-card p-4 space-y-3">
                    <div class="text-xs uppercase tracking-wide text-text-subtle">Key dates</div>
                    <div class="text-sm text-text-base flex justify-between">
                        <span>Next follow-up</span>
                        <span>{{ $opp->next_followup_at ? $opp->next_followup_at->format('M j, Y g:ia') : '—' }}</span>
                    </div>
                    <div class="text-sm text-text-base flex justify-between">
                        <span>Expected close</span>
                        <span>{{ $opp->expected_close_date ? $opp->expected_close_date->format('M j, Y') : '—' }}</span>
                    </div>
                    <div class="text-sm text-text-base flex justify-between">
                        <span>Stage</span><span>{{ ucfirst($opp->stage) }}</span>
                    </div>
                </section>

                <section class="oh-card p-4 space-y-3">
                    <div>
                        <div>
                            <h3 class="text-xs uppercase tracking-wide text-text-subtle">Activity</h3>
                            <p class="text-xs text-text-subtle mt-1">Log follow-ups or notes for your team.</p>
                        </div>
                    </div>
                    <form method="POST"
                        action="{{ route('tenant.opportunities.activities.store', ['tenant' => $tenantId, 'opportunity' => $opp->id]) }}"
                        class="flex flex-col gap-2">
                        @csrf
                        <label class="text-xs text-text-subtle">Add a note</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <input name="body" placeholder="Add note…" class="oh-input h-9 flex-1 min-w-[200px]">
                            <button type="submit" class="oh-btn oh-btn--primary text-xs">Add</button>
                        </div>
                    </form>
                    <div class="space-y-3">
                        @forelse($activities as $activity)
                            <div class="border border-border-default/60 rounded-lg p-3 text-sm">
                                @php
                                    $activityUser = $activity->user
                                        ? trim(($activity->user->first_name ?? '') . ' ' . ($activity->user->last_name ?? '')) ?: ($activity->user->email ?? 'User')
                                        : 'System';
                                @endphp
                                <div class="flex items-center justify-between text-xs text-text-subtle mb-1">
                                    <span>{{ ucfirst($activity->type) }} • {{ $activityUser }}</span>
                                    <span>{{ optional($activity->created_at)->diffForHumans() }}</span>
                                </div>
                                <div class="text-text-base whitespace-pre-line">{{ $activity->body }}</div>
                            </div>
                        @empty
                            <div class="text-text-subtle text-sm">No activity yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
