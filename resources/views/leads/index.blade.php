@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    @php
        use App\Models\Tenant;
        use App\Models\TeamMember;

        // Resolve tenant from route or fallback to user
        $rt = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $rt instanceof Tenant ? $rt->getKey() : (int) $rt;

        // Filters
        $q = request('q', '');
        $st = request('status', '');

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

        $sourcePill = function ($src) {
            $s = strtolower((string) $src);
            return match ($s) {
                'referral' => 'oh-pill oh-pill--success',
                'ads' => 'oh-pill oh-pill--warning',
                'email' => 'oh-pill oh-pill--info',
                default => 'oh-pill',
            };
        };

        $lifecycleLabel = function ($lead) {
            $created = data_get($lead, 'created_at') ? \Illuminate\Support\Carbon::parse($lead->created_at) : null;
            $becameClient = data_get($lead, 'became_client_at')
                ? \Illuminate\Support\Carbon::parse($lead->became_client_at)
                : null;
            $lostAt = data_get($lead, 'lost_at') ? \Illuminate\Support\Carbon::parse($lead->lost_at) : null;
            $closedAt = data_get($lead, 'closed_at') ? \Illuminate\Support\Carbon::parse($lead->closed_at) : null;
            $status = strtolower((string) data_get($lead, 'status', 'new'));

            $age = $created ? $created->diffForHumans(null, true) : null;
            $delta = fn($from, $to) => $from && $to ? $from->diffForHumans($to, true) : null;

            if (in_array($status, ['client']) && $becameClient) {
                return ['label' => 'Client', 'detail' => 'In ' . ($delta($created, $becameClient) ?? '—')];
            }

            if (in_array($status, ['lost']) && $lostAt) {
                return ['label' => 'Lost', 'detail' => 'After ' . ($delta($created, $lostAt) ?? '—')];
            }

            if (in_array($status, ['closed']) && $closedAt) {
                return ['label' => 'Closed', 'detail' => 'At ' . $closedAt->format('M j, Y')];
            }

            return ['label' => 'Open', 'detail' => $age ? $age . ' open' : '—'];
        };

        $initials = function ($text) {
            $parts = preg_split('/\s+/', trim((string) $text));
            $a = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
            $b = strtoupper(mb_substr($parts[1] ?? '', 0, 1));
            return trim($a . $b) ?: 'L';
        };

        $statusOptions = $statusOptions ?? ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'];

        $fallbackPalette = ['#1F3C66', '#5FB4A8', '#EA7D51', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444', '#0EA5E9'];

        $ownerColorMap = TeamMember::where('tenant_id', $tenantId)
            ->whereNotNull('color_hex')
            ->get(['user_id', 'color_hex', 'firstName', 'lastName'])
            ->filter(fn($m) => !empty($m->user_id))
            ->mapWithKeys(function ($m) {
                return [
                    (int) $m->user_id => [
                        'color' => $m->color_hex,
                        'name' => trim(($m->firstName ?? '') . ' ' . ($m->lastName ?? '')),
                    ],
                ];
            })
            ->toArray();

        $ownerColorFor = function ($lead) use ($ownerColorMap, $fallbackPalette) {
            $ownerId = (int) (data_get($lead, 'owner_user_id') ?? data_get($lead, 'owner_id', 0));
            if ($ownerId && isset($ownerColorMap[$ownerId]['color'])) {
                return $ownerColorMap[$ownerId]['color'];
            }
            if ($ownerId) {
                $idx = abs(crc32((string) $ownerId)) % count($fallbackPalette);
                return $fallbackPalette[$idx];
            }
            return '#94A3B8';
        };

        $sourceColorMap = [
            'website' => '#6366F1',
            'manual' => '#0EA5E9',
            'referral' => '#22C55E',
            'ads' => '#F59E0B',
            'email' => '#3B82F6',
            'event' => '#F97316',
            'other' => '#94A3B8',
        ];
        $sourceLabelMap = [
            'website' => 'Website',
            'manual' => 'Manual',
            'referral' => 'Referral',
            'ads' => 'Ads',
            'email' => 'Email',
            'event' => 'Event',
            'other' => 'Other',
        ];
    @endphp


    @php
        $leadsCollection = method_exists($leads, 'getCollection') ? $leads->getCollection() : collect($leads);
        $newCount = $leadsCollection
            ->filter(fn($lead) => strtolower((string) data_get($lead, 'status', '')) === 'new')
            ->count();
        $qualifiedCount = $leadsCollection
            ->filter(fn($lead) => strtolower((string) data_get($lead, 'status', '')) === 'qualified')
            ->count();
        $thisWeekCount = $leadsCollection
            ->filter(function ($lead) {
                $timestamp = data_get($lead, 'submitted_at') ?? data_get($lead, 'created_at');
                return $timestamp ? \Illuminate\Support\Carbon::parse($timestamp)->isCurrentWeek() : false;
            })
            ->count();
    @endphp

    <div class="oh-page space-y-6">

        {{-- Header + Quick Action (Renlo pattern) --}}
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Leads</h1>
                <p class="text-sm text-text-subtle mt-1">Track prospects and keep follow-ups moving.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.leads.create', ['tenant' => $tenantId]) }}"
                    class="oh-btn oh-btn--primary inline-flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs"></i>
                    New Lead
                </a>
            </div>
        </header>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="oh-card p-4">
                <div class="text-xs uppercase tracking-wide text-text-subtle">New</div>
                <div class="mt-1 text-2xl font-semibold text-text-base">{{ $newCount }}</div>
                <div class="text-xs text-text-subtle mt-1">Awaiting follow-up</div>
            </div>
            <div class="oh-card p-4">
                <div class="text-xs uppercase tracking-wide text-text-subtle">This week</div>
                <div class="mt-1 text-2xl font-semibold text-text-base">{{ $thisWeekCount }}</div>
                <div class="text-xs text-text-subtle mt-1">Submitted recently</div>
            </div>
            <div class="oh-card p-4">
                <div class="text-xs uppercase tracking-wide text-text-subtle">Qualified</div>
                <div class="mt-1 text-2xl font-semibold text-text-base">{{ $qualifiedCount }}</div>
                <div class="text-xs text-text-subtle mt-1">Ready to convert</div>
            </div>
        </section>

        {{-- Filter bar --}}
        <section class="oh-card">
            <form method="GET" action="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}"
                class="grid grid-cols-1 md:grid-cols-6 gap-3 p-4">
                <div class="md:col-span-2">
                    <input name="q" value="{{ $q }}" placeholder="Search name, email, company, message…"
                        class="oh-input w-full h-10">
                </div>
                <div>
                    <select name="status" class="oh-select h-10 w-full">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}" @selected($st === $statusOption)>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="priority" class="oh-select h-10 w-full">
                        <option value="">All priorities</option>
                        @foreach ($priorityOptions as $priorityOption)
                            <option value="{{ $priorityOption }}" @selected(request('priority') === $priorityOption)>{{ ucfirst($priorityOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="owner_id" class="oh-select h-10 w-full">
                        <option value="">All owners</option>
                        @foreach ($owners ?? [] as $owner)
                            @php
                                $ownerLabel = trim(($owner->first_name ?? '') . ' ' . ($owner->last_name ?? '')) ?: ($owner->email ?? 'Owner');
                            @endphp
                            <option value="{{ $owner->id }}" @selected((string) request('owner_id') === (string) $owner->id)>{{ $ownerLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="sort" class="oh-select h-10 w-full">
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                        <option value="priority" @selected(request('sort') === 'priority')>Priority</option>
                    </select>
                </div>
                <div class="md:col-span-6 flex items-center gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                    @if ($q !== '' || $st !== '' || request('priority') || request('owner_id') || request('sort'))
                        <a href="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}" class="oh-btn">Reset</a>
                    @endif
                </div>
            </form>
        </section>

        @php
            $ownerKeyItems = $leadsCollection
                ->filter(fn($lead) => !empty(data_get($lead, 'owner_id')))
                ->map(function ($lead) use ($ownerColorFor) {
                    $owner = data_get($lead, 'owner');
                    $ownerName =
                        trim(($owner->first_name ?? '') . ' ' . ($owner->last_name ?? '')) ?:
                        ($owner->username ?? $owner->email ?? 'Owner');

                    return [
                        'id' => (int) data_get($lead, 'owner_id'),
                        'name' => $ownerName,
                        'color' => $ownerColorFor($lead),
                    ];
                })
                ->unique('id')
                ->values();
        @endphp


        @php
            $from = method_exists($leads, 'firstItem') ? $leads->firstItem() : 0;
            $to = method_exists($leads, 'lastItem') ? $leads->lastItem() : $leads->count();
            $total = method_exists($leads, 'total') ? $leads->total() : $leads->count();
        @endphp

        <div class="oh-card p-4 md:p-5 space-y-4">
            {{-- Mobile (compact cards) --}}
            <div class="md:hidden grid gap-3 sm:grid-cols-2">
                @forelse ($leads as $lead)
                    @php
                        $id = data_get($lead, 'id');
                        $name =
                            data_get($lead, 'name') ?:
                            trim((data_get($lead, 'first_name') ?? '') . ' ' . (data_get($lead, 'last_name') ?? ''));
                        $email = data_get($lead, 'email') ?: '—';
                        $phone = data_get($lead, 'phone') ?: '—';
                        $status = data_get($lead, 'status', 'new');
                        $source = data_get($lead, 'source', '—');
                        $sourceKey = strtolower((string) $source);
                        $sourceColor = $sourceColorMap[$sourceKey] ?? '#94A3B8';
                        $sourceLabel = $sourceLabelMap[$sourceKey] ?? ucfirst($sourceKey);
                        $submittedAt = data_get($lead, 'submitted_at') ?? data_get($lead, 'created_at');
                        $submitted = $submittedAt ? \Illuminate\Support\Carbon::parse($submittedAt)->diffForHumans() : '—';
                        $showUrl = route('tenant.leads.show', ['tenant' => $tenantId, 'lead' => $id]);
                    @endphp

                    <article class="oh-card p-4">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-xl grid place-items-center text-xs font-bold"
                                style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary)); border: 1px solid rgba(var(--brand-primary)/.28);">
                                {{ $initials($name ?: $email) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <a href="{{ $showUrl }}" class="font-semibold text-text-base truncate block">
                                    {{ $name ?: $email }}
                                </a>
                                <div class="text-xs text-text-subtle truncate">{{ $email }} · {{ $phone }}</div>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="{{ $statusPill($status) }}">{{ ucfirst((string) $status) }}</span>
                                    <span class="inline-flex items-center gap-1 text-xs text-text-subtle">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $sourceColor }};"></span>
                                        {{ $sourceLabel }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-xs text-text-subtle">
                                        <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $sourceColor }};"></span>
                                        {{ $sourceLabel }}
                                    </span>
                                </div>

                                <div class="mt-2 text-xs text-text-subtle">
                                    Submitted {{ $submitted }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-t border-border-default/60 flex items-center justify-end gap-3">
                            <a href="{{ $showUrl }}"
                                class="text-sm font-semibold text-brand-primary hover:text-brand-secondary">View</a>
                        </div>
                    </article>
                @empty
                    <div class="oh-card p-6 text-center text-text-subtle">
                        No leads found. Try adjusting your filters.
                    </div>
                @endforelse
            </div>

            {{-- Desktop (table) --}}
            <div class="hidden md:block rounded-xl border border-border-default/70 overflow-hidden">
                <div class="overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm">
                        <thead style="background: rgba(var(--surface-muted)/.55);">
                            <tr class="text-left text-text-subtle border-b border-border-default/60">
                                <th class="px-5 py-2.5 font-medium">Lead</th>
                                <th class="px-5 py-2.5 font-medium">Status</th>
                                <th class="px-5 py-2.5 font-medium">Source</th>
                                <th class="px-5 py-2.5 font-medium">Submitted</th>
                                <th class="px-5 py-2.5 font-medium text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border) / .35);">
                            @forelse($leads as $lead)
                                @php
                                    $id = data_get($lead, 'id');
                                    $name =
                                        data_get($lead, 'name') ?:
                                        trim(
                                            (data_get($lead, 'first_name') ?? '') .
                                                ' ' .
                                                (data_get($lead, 'last_name') ?? ''),
                                        );
                                    $email = data_get($lead, 'email') ?: '—';
                                    $status = data_get($lead, 'status', 'new');
                                    $source = data_get($lead, 'source', '—');
                                    $sourceKey = strtolower((string) $source);
                                    $sourceColor = $sourceColorMap[$sourceKey] ?? '#94A3B8';
                                    $sourceLabel = $sourceLabelMap[$sourceKey] ?? ucfirst($sourceKey);
                                    $submittedAt = data_get($lead, 'submitted_at') ?? data_get($lead, 'created_at');
                                    $submitted = $submittedAt ? \Illuminate\Support\Carbon::parse($submittedAt)->diffForHumans() : '—';

                                    $showUrl = route('tenant.leads.show', ['tenant' => $tenantId, 'lead' => $id]);
                                @endphp

                                <tr class="group hover:bg-surface-accent/40 transition-colors">
                                    {{-- Lead (row-click target lives here) --}}
                                    <td class="px-5 py-2.5 relative">
                                        {{-- Stretch-link for row click (keeps actions clickable because they are z-10) --}}
                                        <a href="{{ $showUrl }}"
                                            class="after:absolute after:inset-0 after:content-[''] after:z-0"
                                            aria-label="Open lead {{ $name ?: $email }}"></a>

                                        <div class="relative z-10 flex items-center gap-3 min-w-0">
                                            <div class="h-9 w-9 rounded-full ring-1 ring-[rgb(var(--border)/.6)] grid place-items-center text-[11px] font-bold shrink-0"
                                                style="background: rgba(var(--brand-primary)/.14); color: rgb(var(--brand-primary));">
                                                {{ $initials($name ?: $email) }}
                                            </div>

                                            <div class="min-w-0">
                                                <div class="font-semibold text-text-base truncate">
                                                    {{ $name ?: $email }}
                                                </div>
                                                <div class="text-xs text-text-subtle truncate">{{ $email }} · {{ data_get($lead, 'phone') ?: '—' }}</div>
                                                <div class="mt-1 flex flex-col gap-0.5 text-[11px] text-text-subtle">
                                                    <span>Source: {{ $sourceLabel }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-2.5">
                                        <span class="{{ $statusPill($status) }}">{{ ucfirst((string) $status) }}</span>
                                    </td>

                                    <td class="px-5 py-2.5">
                                        <div class="inline-flex items-center gap-1 text-xs text-text-subtle">
                                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $sourceColor }};"></span>
                                            {{ $sourceLabel }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-2.5 text-text-subtle text-sm">
                                        {{ $submitted }}
                                    </td>

                                    <td class="px-5 py-2.5 text-right relative z-10">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ $showUrl }}" class="oh-btn">View</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-text-subtle">
                                        No leads found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination (if $leads is LengthAwarePaginator) --}}
            @if (method_exists($leads, 'links'))
                @php $pager = $leads->appends(request()->only(['q', 'status', 'priority', 'owner_id', 'sort'])); @endphp
                @if ($pager->hasPages())
                    <div class="pt-2 border-t border-border-default/60 text-sm text-text-subtle space-y-3">
                        <div>Showing {{ $from }} to {{ $to }} of {{ $total }} results</div>
                        <div class="flex items-center justify-between">
                            @if ($pager->onFirstPage())
                                <span class="oh-btn opacity-50 pointer-events-none">Previous</span>
                            @else
                                <a href="{{ $pager->previousPageUrl() }}" class="oh-btn">Previous</a>
                            @endif
                            @if ($pager->hasMorePages())
                                <a href="{{ $pager->nextPageUrl() }}" class="oh-btn">Next</a>
                            @else
                                <span class="oh-btn opacity-50 pointer-events-none">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('toggleKey')?.addEventListener('click', () => {
                const el = document.getElementById('colorKey');
                if (!el) return;
                const shown = el.style.display !== 'none';
                el.style.display = shown ? 'none' : '';
                document.getElementById('toggleKey')?.setAttribute('aria-expanded', String(!shown));
            });
        </script>
    @endpush
@endsection
