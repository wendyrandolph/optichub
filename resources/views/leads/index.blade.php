@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    @php
        use App\Models\Tenant;

        // Resolve tenant from route or fallback to user
        $rt = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $rt instanceof Tenant ? $rt->getKey() : (int) $rt;

        // Filters
        $q = request('q', '');
        $st = request('status', '');

        // Status → pill classes
        $statusClass = function ($status) {
            $s = strtolower((string) $status);
            return match (true) {
                str_contains($s, 'new')
                    => 'bg-blue-100 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
                str_contains($s, 'contact')
                    => 'bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-200 dark:border-purple-800',
                str_contains($s, 'interested'),
                str_contains($s, 'qualified')
                    => 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800',
                str_contains($s, 'client'),
                str_contains($s, 'won')
                    => 'bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-800',
                str_contains($s, 'closed'),
                str_contains($s, 'lost')
                    => 'bg-gray-100 text-gray-700 border border-gray-200 dark:bg-slate-800/60 dark:text-slate-200 dark:border-slate-700',
                default
                    => 'bg-gray-100 text-gray-700 border border-gray-200 dark:bg-slate-800/60 dark:text-slate-200 dark:border-slate-700',
            };
        };

        // For selects
        $statusOptions = ['new', 'contacted', 'interested', 'client', 'closed', 'lost'];
    @endphp


    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Header + Quick Action --}}
        <header class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Leads</h1>
                <p class="text-sm text-text-subtle mt-1">Track prospects and keep follow-ups moving.</p>
            </div>

            <a href="{{ route('tenant.leads.create', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg text-sm font-medium text-white
              bg-gradient-to-b from-brand-primary to-blue-700 hover:brightness-110 transition">
                <i class="fa-solid fa-plus mr-2"></i> New Lead
            </a>
        </header>

        {{-- Toolbar (search + status) --}}
        <div class="rounded-xl bg-surface-card/70 border border-border-default/60 mb-8">
            <form method="GET" action="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}"
                class="p-4 md:p-5 flex flex-col md:flex-row md:flex-wrap gap-3 md:items-center">
                <div class="flex-1 md:flex-none md:w-[320px]">
                    <input name="q" value="{{ $q }}" placeholder="Search name, email, notes…"
                        class="w-full h-10 rounded-lg bg-white/70 dark:bg-gray-900/40 text-text-base px-3 text-sm
                    border border-border-default focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                </div>

                <select name="status"
                    class="h-10 rounded-lg bg-white/70 dark:bg-gray-900/40 text-text-base px-3 text-sm
                   border border-border-default focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                    <option value="" @selected($st === '')>All statuses</option>
                    <option value="new" @selected($st === 'new')>New</option>
                    <option value="contacted" @selected($st === 'contacted')>Contacted</option>
                    <option value="interested" @selected($st === 'interested')>Interested</option>
                    <option value="client" @selected($st === 'client')>Client</option>
                    <option value="closed" @selected($st === 'closed')>Closed</option>
                    <option value="lost" @selected($st === 'lost')>Lost</option>
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                        class="h-10 px-4 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Apply
                    </button>
                    @if ($q !== '' || $st !== '')
                        <a href="{{ route('tenant.leads.index', ['tenant' => $tenantId]) }}"
                            class="h-10 px-4 rounded-lg bg-white/70 dark:bg-gray-900/40 text-text-base text-sm border border-border-default">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @php
            // status & source chips
            $statusPill = function ($status) {
                $s = strtolower((string) $status);
                return match (true) {
                    str_contains($s, 'new')
                        => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
                    str_contains($s, 'contact')
                        => 'bg-indigo-50 text-indigo-700 border border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-200 dark:border-indigo-800',
                    str_contains($s, 'interested')
                        => 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-200 dark:border-purple-800',
                    str_contains($s, 'client')
                        => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-800',
                    str_contains($s, 'closed')
                        => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800/50 dark:text-slate-200 dark:border-slate-700',
                    str_contains($s, 'lost')
                        => 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-200 dark:border-rose-800',
                    default
                        => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800/50 dark:text-slate-200 dark:border-slate-700',
                };
            };
            $sourceChip = function ($src) {
                $s = strtolower((string) $src);
                return match ($s) {
                    'web'
                        => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
                    'referral'
                        => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-800',
                    'ads'
                        => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-800',
                    'email'
                        => 'bg-violet-50 text-violet-700 border border-violet-200 dark:bg-violet-900/30 dark:text-violet-200 dark:border-violet-800',
                    'event'
                        => 'bg-fuchsia-50 text-fuchsia-700 border border-fuchsia-200 dark:bg-fuchsia-900/30 dark:text-fuchsia-200 dark:border-fuchsia-800',
                    default
                        => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800/50 dark:text-slate-200 dark:border-slate-700',
                };
            };
            $initials = function ($text) {
                $parts = preg_split('/\s+/', trim((string) $text));
                $first = strtoupper(mb_substr($parts[0] ?? '', 0, 1));
                $second = strtoupper(mb_substr($parts[1] ?? '', 0, 1));
                return $first . $second;
            };
        @endphp

        {{-- Cards Grid --}}
        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @forelse ($leads as $lead)
                @php
                    $name =
                        data_get($lead, 'name') ?:
                        trim(data_get($lead, 'first_name') . ' ' . data_get($lead, 'last_name'));
                    $email = data_get($lead, 'email', '—');
                    $phone = data_get($lead, 'phone', '—');
                    $status = data_get($lead, 'status', 'New');
                    $source = data_get($lead, 'source', 'other');
                    $owner = data_get($lead, 'owner.name') ?? (data_get($lead, 'owner_name') ?? 'Unassigned');
                    $id = data_get($lead, 'id');
                    $created = data_get($lead, 'created_at');
                    $createdFmt = $created ? \Carbon\Carbon::parse($created)->format('M j, Y') : '—';
                @endphp

                <article
                    class="group rounded-2xl bg-white/80 dark:bg-gray-900/50 border border-slate-200/70 dark:border-slate-800/70
         hover:border-slate-300 hover:shadow-lg hover:shadow-slate-900/5 transition-all duration-200"
                    @if ($createdFmt) title="Added {{ $createdFmt }}" @endif>
                    <div class="p-4">
                        {{-- Top row: avatar + name/email + status --}}
                        <div class="flex items-start gap-3">
                            <div
                                class="h-9 w-9 rounded-full bg-blue-600/10 text-blue-700 dark:text-blue-300 grid place-items-center text-xs font-bold">
                                {{ $initials($name ?: $email) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $name ?: $email }}
                                </h3>
                                <p class="text-xs text-gray-500 truncate">{{ $email }}</p>
                            </div>

                            <span
                                class="px-2 py-0.5 rounded-md text-[11px] font-medium shrink-0 {{ $statusPill($status) }}">
                                {{ ucfirst($status) }}
                            </span>
                        </div>

                        {{-- Chips: source + owner + phone (as a chip instead of footer) --}}
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="px-2 py-0.5 rounded-md text-[11px] {{ $sourceChip($source) }}">
                                {{ ucfirst($source) }}
                            </span>
                            <span
                                class="px-2 py-0.5 rounded-md text-[11px] bg-slate-50 text-slate-500 border border-slate-200 dark:bg-slate-800/40 dark:text-slate-300">
                                <i class="fa-regular fa-clock mr-1"></i>{{ $createdFmt }}
                            </span>

                            <span
                                class="px-2 py-0.5 rounded-md text-[11px] bg-slate-50 text-slate-600 border border-slate-200
                   dark:bg-slate-800/40 dark:text-slate-300 dark:border-slate-700">
                                <i class="fa-regular fa-user mr-1"></i>{{ $owner }}
                            </span>

                            @if ($phone && $phone !== '—')
                                <a href="tel:{{ preg_replace('/\D+/', '', $phone) }}"
                                    class="px-2 py-0.5 rounded-md text-[11px] bg-slate-50 text-slate-600 border border-slate-200
                  hover:bg-slate-100 dark:bg-slate-800/40 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-800/70">
                                    <i class="fa-regular fa-phone mr-1"></i>{{ $phone }}
                                </a>
                            @endif
                        </div>

                        {{-- Notes (clamped) --}}
                        @if (!empty($lead['notes']))
                            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ $lead['notes'] }}</p>
                        @endif
                    </div>

                    {{-- Action bar only (no meta here) --}}
                    <div
                        class="px-4 py-2 border-t border-slate-200/70 dark:border-slate-800/70 flex items-center justify-end gap-2">
                        <a href="{{ route('tenant.leads.show', ['tenant' => $tenantId, 'lead' => $id]) }}"
                            class="px-2.5 py-1.5 rounded-md text-sm bg-surface-card/60 hover:bg-surface-card/90 text-text-base">
                            View
                        </a>
                        <a href="{{ route('tenant.leads.edit', ['tenant' => $tenantId, 'lead' => $id]) }}"
                            class="px-2.5 py-1.5 rounded-md text-sm bg-surface-card/60 hover:bg-surface-card/90 text-text-base">
                            Edit
                        </a>
                        <form method="POST"
                            action="{{ route('tenant.leads.destroy', ['tenant' => $tenantId, 'lead' => $id]) }}"
                            onsubmit="return confirm('Delete this lead?');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="px-2.5 py-1.5 rounded-md text-sm bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/20 dark:text-rose-300">
                                Delete
                            </button>
                        </form>
                    </div>
                </article>

            @empty
                <div
                    class="col-span-full rounded-2xl bg-white/80 dark:bg-gray-900/50 border border-dashed border-slate-300 dark:border-slate-700 p-10 text-center text-text-subtle">
                    No leads found. Try adjusting your filters.
                </div>
            @endforelse
        </section>

        {{-- Pagination (if $leads is LengthAwarePaginator) --}}
        @if (method_exists($leads, 'links'))
            <div>
                {{ $leads->appends(request()->only(['q', 'status']))->links() }}
            </div>
        @endif
    </div>
@endsection
