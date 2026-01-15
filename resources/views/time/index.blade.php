{{-- resources/views/time/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Time')

@section('content')
    @php
        use App\Models\Tenant;

        // Resolve tenant (works with {tenant} param, relationship, or session)
        $routeTenant = request()->route('tenant') ?? null;
        $tenantId = match (true) {
            $routeTenant instanceof Tenant => $routeTenant->getKey(),
            is_numeric($routeTenant) => (int) $routeTenant,
            default => auth()->user()->tenant_id ?? (session('tenant_id') ?? null),
        };

        // Expect these from the controller (safe defaults here):
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\App\Models\TimeEntry[] $entries */
        $entries = $entries ?? collect(); // pass paginator from controller
        $summary = $summary ?? [
            'hours_today' => 0,
            'hours_wtd' => 0,
            'hours_mtd' => 0,
            'unbilled' => 0,
            'unbilled_value' => 0,
        ];
        $filters = $filters ?? [
            'range' => request('range', 'wtd'),
            'member_id' => request('member_id'),
            'project_id' => request('project_id'),
            'q' => request('q'),
            'billable' => request('billable'),
        ];

        // Helpers for links that preserve filters
        $withQuery = function (array $extra = []) {
            return request()->fullUrlWithQuery($extra);
        };
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header / Actions --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Time</p>
                <h1 class="text-2xl font-semibold text-text-base">Time Entries</h1>
                <p class="text-sm text-text-subtle">Track and review billable hours across projects and team members.</p>
            </div>

            <div class="flex items-center gap-2">
                @if ($tenantId)
                    <button type="button" id="open-log-modal" class="oh-btn oh-btn--primary">
                        <i class="fa-regular fa-clock text-[12px] mr-2"></i>
                        Log Time
                    </button>
                @else
                    <span class="text-sm text-rose-500">Tenant not resolved.</span>
                @endif
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="oh-card p-4 border border-border-default/70">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Today</div>
                <div class="text-2xl font-semibold text-text-base">{{ number_format($summary['hours_today'], 2) }}h</div>
            </div>
            <div class="oh-card p-4 border border-border-default/70">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Week-to-Date</div>
                <div class="text-2xl font-semibold text-text-base">{{ number_format($summary['hours_wtd'], 2) }}h</div>
            </div>
            <div class="oh-card p-4 border border-border-default/70">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Month-to-Date</div>
                <div class="text-2xl font-semibold text-text-base">{{ number_format($summary['hours_mtd'], 2) }}h</div>
            </div>
            <div class="oh-card p-4 border border-border-default/70">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">Unbilled</div>
                <div class="text-2xl font-semibold text-text-base">{{ number_format($summary['unbilled'], 2) }}h</div>
                <div class="text-xs text-text-subtle mt-1">Est. ${{ number_format($summary['unbilled_value'] ?? 0, 2) }}
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('tenant.time.index', ['tenant' => $tenantId]) }}">
            <div class="oh-card border border-border-default/60 p-4 md:p-5 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    {{-- Range --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Range</span>
                        <select name="range" class="oh-select h-10">
                            @foreach (['today' => 'Today', 'wtd' => 'Week to Date', 'mtd' => 'Month to Date', '30d' => 'Last 30 Days', 'all' => 'All'] as $val => $label)
                                <option value="{{ $val }}" @selected($filters['range'] === $val)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Member --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Member</span>
                        <select name="member_id" class="oh-select h-10">
                            <option value="">All</option>
                            @foreach ($members ?? [] as $m)
                                <option value="{{ $m->id }}" @selected($filters['member_id'] == $m->id)>{{ $m->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Project --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Project</span>
                        <select name="project_id" class="oh-select h-10">
                            <option value="">All</option>
                            @foreach ($projects ?? [] as $p)
                                <option value="{{ $p->id }}" @selected($filters['project_id'] == $p->id)>{{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Billable --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Billable</span>
                        <select name="billable" class="oh-select h-10">
                            <option value="">All</option>
                            <option value="1" @selected($filters['billable'] === '1')>Billable</option>
                            <option value="0" @selected($filters['billable'] === '0')>Non-billable</option>
                        </select>
                    </label>

                    {{-- Billing status --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Billing</span>
                        <select name="billing_status" class="oh-select h-10">
                            @php $bs = $filters['billing_status'] ?? ''; @endphp
                            <option value="">All</option>
                            <option value="unbilled" @selected($bs === 'unbilled')>Unbilled</option>
                            <option value="billed" @selected($bs === 'billed')>Billed</option>
                            <option value="non_billable" @selected($bs === 'non_billable')>Non-billable</option>
                        </select>
                    </label>

                    {{-- Search --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Search</span>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                            placeholder="Notes, task, project…" class="oh-input h-10" />
                    </label>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary">Apply</button>
                    <a href="{{ route('tenant.time.index', ['tenant' => $tenantId]) }}" class="oh-btn">Reset</a>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <form method="POST" action="{{ route('tenant.time.bulk-bill', ['tenant' => $tenantId]) }}" id="bulk-container">
            @csrf
            <div class="oh-card border border-border-default shadow-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[rgba(var(--surface-muted)/.6)] text-text-subtle">
                            <tr>
                                <th class="px-4 py-2 w-10">
                                    <input type="checkbox" id="select-all" class="oh-input h-4 w-4">
                                </th>
                                <th class="px-4 py-2 text-left font-medium">Date</th>
                                <th class="px-4 py-2 text-left font-medium">Member</th>
                                <th class="px-4 py-2 text-left font-medium">Project</th>
                                <th class="px-4 py-2 text-left font-medium">Task</th>
                                <th class="px-4 py-2 text-right font-medium">Hours</th>
                                <th class="px-4 py-2 text-left font-medium">Billing</th>
                                <th class="px-4 py-2 text-left font-medium">Notes</th>
                                <th class="px-4 py-2 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border)/.35);">
                            @forelse($entries as $entry)
                                @php
                                    $billing = $entry->billing_status ?? 'unbilled';
                                    $pill = match ($billing) {
                                        'billed' => 'oh-pill oh-pill--info',
                                        'non_billable' => 'oh-pill',
                                        default => 'oh-pill oh-pill--muted',
                                    };
                                @endphp
                                <tr class="hover:bg-surface-accent/40 transition-colors">
                                    <td class="px-4 py-3 align-middle">
                                        <input type="checkbox" name="entry_ids[]" value="{{ $entry->id }}"
                                            class="row-check oh-input h-4 w-4" data-billing="{{ $billing }}"
                                            data-client="{{ optional($entry->project)->contact_id }}">
                                    </td>
                                    <td class="px-4 py-3 text-text-base">
                                        {{ optional($entry->start_time ?? $entry->date)->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-text-base">
                                        {{ $entry->user->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-text-base">
                                        @if ($entry->project_id)
                                            <a href="{{ route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $entry->project_id]) }}"
                                                class="text-brand-primary hover:text-brand-secondary">
                                                {{ $entry->project->project_name ?? ($entry->project->name ?? '—') }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-text-base">
                                        {{ $entry->task->title ?? ($entry->task_name ?? '—') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-text-base">
                                        {{ number_format($entry->hours ?? ($entry->duration_hours ?? 0), 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($billing === 'billed' && $entry->invoice_id)
                                            <a href="{{ route('tenant.invoices.show', ['tenant' => $tenantId, 'invoice' => $entry->invoice_id]) }}"
                                                class="{{ $pill }} text-[11px] inline-flex items-center gap-1" title="Billed via invoice">
                                                Billed
                                                <i class="fa-regular fa-arrow-up-right-from-square text-[10px]"></i>
                                            </a>
                                        @else
                                            <span class="{{ $pill }} text-[11px] inline-flex items-center" title="{{ $billing === 'unbilled' ? 'Billable & not invoiced yet' : 'Marked non-billable' }}">
                                                {{ ucfirst(str_replace('_', ' ', $billing)) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 max-w-[28ch] truncate text-text-subtle"
                                        title="{{ $entry->notes }}">
                                        {{ $entry->notes ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            @php
                                                $editPayload = [
                                                    'id' => $entry->id,
                                                    'project_id' => $entry->project_id,
                                                    'task_id' => $entry->task_id,
                                                    'date' => optional($entry->date ?? $entry->start_time)?->toDateString(),
                                                    'hours' => $entry->hours,
                                                    'hourly_rate' => $entry->hourly_rate,
                                                    'user_id' => $entry->user_id,
                                                    'billable' => $entry->billable,
                                                    'notes' => $entry->notes,
                                                ];
                                            @endphp
                                            <button type="button"
                                                class="oh-icon-btn edit-time-trigger"
                                                title="Edit"
                                                aria-label="Edit time entry"
                                                data-entry='@json($editPayload)'>
                                                <i class="fa-regular fa-pen-to-square text-[12px]"></i>
                                            </button>
                                            <button type="submit" form="delete-entry-{{ $entry->id }}"
                                                class="oh-icon-btn text-rose-600 delete-time-btn" title="Delete"
                                                aria-label="Delete time entry"
                                                data-billed="{{ $billing === 'billed' ? '1' : '0' }}">
                                                <i class="fa-regular fa-trash-can text-[12px]"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-text-subtle">
                                        <div class="space-y-3">
                                            <p class="font-semibold text-text-base">No time entries yet</p>
                                            <p class="text-sm">Log your first entry to start tracking hours.</p>
                                            @if ($tenantId)
                                                <button type="button"
                                                    class="oh-btn oh-btn--primary mt-2 log-time-trigger">
                                                    <i class="fa-regular fa-plus text-[12px] mr-1"></i> Log Time
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if (method_exists($entries, 'hasPages') && $entries->hasPages())
                    @php $pager = $entries->appends(request()->query()); @endphp
                    <div class="px-4 py-3 border-t border-border-default/70 text-sm text-text-subtle space-y-3">
                        <div>Showing {{ $pager->firstItem() }} to {{ $pager->lastItem() }} of {{ $pager->total() }} results</div>
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
            </div>

            {{-- Bulk bar --}}
            <div id="bulk-bar"
                class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 bg-white border border-border-default shadow-card rounded-full px-4 py-2 flex items-center gap-2 z-30">
                <button type="button" id="bulk-invoice" class="oh-btn oh-btn--primary oh-btn--sm">Add to
                    Invoice</button>
                <button type="submit" formaction="{{ route('tenant.time.bulk-bill', ['tenant' => $tenantId]) }}"
                    name="mark_non_billable" value="1" class="oh-btn oh-btn--secondary oh-btn--sm">Mark
                    Non-billable</button>
                <button type="submit" formaction="{{ route('tenant.time.bulk-bill', ['tenant' => $tenantId]) }}"
                    name="delete" value="1" class="oh-btn oh-btn--danger oh-btn--sm" oncl
                    ick="return confirm('Delete selected time entries?');">Delete</button>
            </div>
        </form>
        {{-- standalone delete forms to avoid nested form issues --}}
        @foreach ($entries as $entry)
            <form id="delete-entry-{{ $entry->id }}" method="POST"
                action="{{ route('tenant.time.destroy', ['tenant' => $tenantId, 'entry' => $entry->id]) }}">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>
        {{-- Log time modal --}}
    @if ($tenantId)
        <div id="log-time-modal" class="fixed inset-0 z-50 hidden">
            {{-- overlay --}}
            <button type="button" id="close-log-modal-overlay" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"
                aria-label="Close modal"></button>

            {{-- centering container --}}
            <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 overflow-y-auto">
                <div class="oh-card w-full max-w-xl border border-border-default shadow-card relative">
                    <div class="flex items-center justify-between p-4 border-b border-border-default/70">
                        <h3 class="text-lg font-semibold text-text-base">Log Time</h3>
                        <button type="button" id="close-log-modal" class="oh-icon-btn" aria-label="Close modal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('tenant.time.store', ['tenant' => $tenantId]) }}"
                        class="p-4 space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Project <span class="text-rose-500">*</span></span>
                                <select name="project_id" class="oh-select h-10 text-text-base" required>
                                    <option value="">Select project</option>
                                    @foreach ($projects ?? [] as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->name ?? ($p->project_name ?? 'Unnamed project') }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Task (optional)</span>
                                <select name="task_id" class="oh-select h-10">
                                    <option value="">Select task</option>
                                    @foreach ($tasks ?? [] as $t)
                                        <option value="{{ $t->id }}">{{ $t->title }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Date</span>
                                <input type="date" name="date" value="{{ now()->toDateString() }}"
                                    class="oh-input h-10">
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Hours <span class="text-rose-500">*</span></span>
                                <input type="number" step="0.01" min="0.01" name="hours"
                                    class="oh-input h-10" required>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Member</span>
                                <select name="user_id" class="oh-select h-10 text-text-base">
                                    @foreach ($members ?? [] as $m)
                                        <option value="{{ $m->id }}" @selected(auth()->id() == $m->id)>
                                            {{ $m->first_name ? trim($m->first_name . ' ' . $m->last_name) : $m->username ?? $m->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Hourly rate (optional)</span>
                                <input type="number" step="0.01" min="0" name="hourly_rate"
                                    class="oh-input h-10" placeholder="Inherit project rate if blank">
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm md:col-span-2">
                                <input type="hidden" name="billable" value="0">
                                <input type="checkbox" name="billable" value="1" class="oh-input h-4 w-4" checked>
                                <span class="text-text-base">Billable</span>
                            </label>
                        </div>

                        <div class="border border-border-default/70 rounded-lg">
                            <button type="button" id="toggle-optional"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm text-text-base">
                                <span>Optional details</span>
                                <i class="fa-solid fa-chevron-down text-[10px]" id="optional-icon"></i>
                            </button>

                            <div id="optional-body" class="hidden px-3 pb-3 space-y-3">
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Notes (optional)</span>
                                    <textarea name="notes" rows="3" class="oh-input" placeholder="Add a short note"></textarea>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="oh-btn" id="cancel-log">Cancel</button>
                            <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

            {{-- Edit time modal (same placement as Log Time) --}}
            <div id="edit-time-modal" class="fixed inset-0 z-50 hidden">
                <button type="button" id="close-edit-modal-overlay" class="absolute inset-0 bg-black/40 backdrop-blur-[2px]"
                    aria-label="Close modal"></button>
                <div class="relative flex min-h-screen items-center justify-center p-4 sm:p-6 overflow-y-auto">
                    <div class="oh-card w-full max-w-xl border border-border-default shadow-card relative">
                    <div class="flex items-center justify-between p-4 border-b border-border-default/70">
                        <h3 class="text-lg font-semibold text-text-base">Edit Time Entry</h3>
                        <button type="button" id="close-edit-modal" class="oh-icon-btn" aria-label="Close modal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form id="edit-time-form" method="POST" action="" class="p-4 space-y-5">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Project <span class="text-rose-500">*</span></span>
                                <select name="project_id" class="oh-select h-10 text-text-base" required>
                                    <option value="">Select project</option>
                                    @foreach ($projects ?? [] as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->name ?? ($p->project_name ?? 'Unnamed project') }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Task (optional)</span>
                                <select name="task_id" class="oh-select h-10" id="edit-modal-task">
                                    <option value="">Select task</option>
                                </select>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Date</span>
                                <input type="date" name="date" class="oh-input h-10">
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Hours <span class="text-rose-500">*</span></span>
                                <input type="number" step="0.01" min="0.01" name="hours"
                                    class="oh-input h-10" required>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Member</span>
                                <select name="user_id" class="oh-select h-10 text-text-base">
                                    @foreach ($members ?? [] as $m)
                                        <option value="{{ $m->id }}">
                                            {{ $m->first_name ? trim($m->first_name . ' ' . $m->last_name) : $m->username ?? $m->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="grid gap-1 text-sm">
                                <span class="text-text-subtle">Hourly rate (optional)</span>
                                <input type="number" step="0.01" min="0" name="hourly_rate"
                                    class="oh-input h-10" placeholder="Inherit project rate if blank">
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm md:col-span-2">
                                <input type="hidden" name="billable" value="0">
                                <input type="checkbox" name="billable" value="1" class="oh-input h-4 w-4">
                                <span class="text-text-base">Billable</span>
                            </label>
                        </div>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Notes</span>
                            <textarea name="notes" rows="3" class="oh-input" placeholder="Optional notes"></textarea>
                        </label>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="oh-btn" id="cancel-edit-modal">Cancel</button>
                            <button type="submit" class="oh-btn oh-btn--primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('log-time-modal');
                if (!modal) return;

                const openBtn = document.getElementById('open-log-modal');
                const openBtns = document.querySelectorAll('.log-time-trigger');

                const closeBtn = document.getElementById('close-log-modal');
                const cancelBtn = document.getElementById('cancel-log');
                const overlayBtn = document.getElementById('close-log-modal-overlay'); // <-- add this

                const optionalBtn = document.getElementById('toggle-optional');
                const optionalBody = document.getElementById('optional-body');
                const optionalIcon = document.getElementById('optional-icon');
                const projectSelect = modal.querySelector('select[name="project_id"]');
                const taskSelect = modal.querySelector('select[name="task_id"]');
                @php
                    $tasksPayload = ($tasks ?? collect())->map(function ($t) {
                        return [
                            'id' => $t->id,
                            'title' => $t->title,
                            'project_id' => $t->project_id,
                        ];
                    });
                @endphp
                const tasksByProject = @json($tasksPayload);
                const editModal = document.getElementById('edit-time-modal');
                const editForm = document.getElementById('edit-time-form');
                const editTaskSelect = document.getElementById('edit-modal-task');
                const editProjectSelect = editModal?.querySelector('select[name="project_id"]');


                const lockScroll = () => document.documentElement.classList.add('overflow-hidden');
                const unlockScroll = () => document.documentElement.classList.remove('overflow-hidden');

                function openModal() {
                    modal.classList.remove('hidden');
                    lockScroll();

                    // optional: autofocus first input/select for nicer UX
                    const firstField = modal.querySelector('input, select, textarea, button');
                    firstField?.focus?.();
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    unlockScroll();
                }

                // Open
                openBtn?.addEventListener('click', openModal);
                openBtns.forEach(btn => btn.addEventListener('click', openModal));

                // Close
                closeBtn?.addEventListener('click', closeModal);
                cancelBtn?.addEventListener('click', closeModal);
                overlayBtn?.addEventListener('click', closeModal);

                // Esc to close
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });

                function filterTasksForSelect(selectEl, projectId) {
                    if (!selectEl) return;
                    const pid = projectId ? Number(projectId) : null;
                    const prev = selectEl.value;
                    selectEl.innerHTML = '<option value="">Select task</option>';
                    tasksByProject
                        .filter(t => pid && Number(t.project_id) === pid)
                        .forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.id;
                            opt.textContent = t.title;
                            selectEl.appendChild(opt);
                        });
                    if (prev && [...selectEl.options].some(o => o.value === prev)) {
                        selectEl.value = prev;
                    }
                }

                // Optional details toggle
                optionalBtn?.addEventListener('click', () => {
                    optionalBody?.classList.toggle('hidden');
                    optionalIcon?.classList.toggle('rotate-180');
                });

                // Filter tasks by project
                function populateTasks(projectId) {
                    if (!taskSelect) return;
                    const prev = taskSelect.value;
                    taskSelect.innerHTML = '<option value="">Select task</option>';
                    const pid = projectId ? Number(projectId) : null;
                    tasksByProject
                        .filter(t => pid && Number(t.project_id) === pid)
                        .forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.id;
                            opt.textContent = t.title;
                            taskSelect.appendChild(opt);
                        });
                    if (prev && [...taskSelect.options].some(o => o.value === prev)) {
                        taskSelect.value = prev;
                    }
                }
                projectSelect?.addEventListener('change', (e) => populateTasks(e.target.value));
                populateTasks(projectSelect?.value || null);

                // Edit modal open/close
                const editOpenButtons = document.querySelectorAll('.edit-time-trigger');
                const editCloseButtons = [document.getElementById('close-edit-modal'), document.getElementById('close-edit-modal-overlay'), document.getElementById('cancel-edit-modal')];

                function openEditModal(entry) {
                    if (!editModal || !editForm) return;
                    // set action
                    editForm.action = "{{ url('/' . ($tenantId ?? $tenant->id ?? request()->route('tenant')) . '/time') }}/" + entry.id;

                    // populate fields
                    editProjectSelect.value = entry.project_id || '';
                    filterTasksForSelect(editTaskSelect, entry.project_id);
                    if (entry.task_id) {
                        editTaskSelect.value = entry.task_id;
                    } else {
                        editTaskSelect.value = '';
                    }
                    editForm.querySelector('input[name="date"]').value = entry.date || '';
                    editForm.querySelector('input[name="hours"]').value = entry.hours || '';
                    editForm.querySelector('input[name="hourly_rate"]').value = entry.hourly_rate ?? '';
                    editForm.querySelector('select[name="user_id"]').value = entry.user_id || '';
                    const billableField = editForm.querySelector('input[name="billable"][type="checkbox"]');
                    billableField.checked = !!entry.billable;
                    editForm.querySelector('textarea[name="notes"]').value = entry.notes || '';

                    editModal.classList.remove('hidden');
                    lockScroll();
                }

                editOpenButtons.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const data = btn.dataset.entry ? JSON.parse(btn.dataset.entry) : null;
                        if (data) openEditModal(data);
                    });
                });

                editCloseButtons.forEach(btn => {
                    btn?.addEventListener('click', () => {
                        editModal?.classList.add('hidden');
                        unlockScroll();
                    });
                });

                // filter tasks in edit modal on project change
                editProjectSelect?.addEventListener('change', (e) => filterTasksForSelect(editTaskSelect, e.target.value));

                // Delete guardrails
                document.querySelectorAll('.delete-time-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const billed = btn.dataset.billed === '1';
                        const msg = billed
                            ? 'This time entry is billed. Deleting it may desync invoices. Admin approval recommended. Continue?'
                            : 'Delete this time entry?';
                        if (!confirm(msg)) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });
                });
            });
        </script>
    @endpush

@endsection
