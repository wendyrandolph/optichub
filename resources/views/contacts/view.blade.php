@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp
    @php
        // Resolve contact + tenant context
        $contact = $contact ?? ($client ?? null);
        $tenantId = $tenant ?? (auth()->user()->tenant_id ?? null);

        // Identity
        $firstName = data_get($contact, 'first_name') ?? (data_get($contact, 'firstName') ?? '');
        $lastName = data_get($contact, 'last_name') ?? (data_get($contact, 'lastName') ?? '');
        $fullName = trim($firstName . ' ' . $lastName) ?: data_get($contact, 'name') ?? 'Contact';
        $email = data_get($contact, 'email');
        $phone = data_get($contact, 'phone_formatted') ?? data_get($contact, 'phone');

        // Company
        $company =
            optional($contact->company ?? ($contact->clientCompany ?? null))->name ??
            data_get($contact, 'company_name');
        $companyId =
            optional($contact->company ?? ($contact->clientCompany ?? null))->id ??
            data_get($contact, 'client_company_id');

        // Status / portal
        $status = strtolower(data_get($contact, 'status', 'active'));
        $hasLogin = (bool) (data_get($contact, 'has_login') ?? optional($contact->userAccount ?? null)->exists);
        $invitedAt = data_get($contact, 'invited_at');
        $invited = !$hasLogin && !empty($invitedAt);

        // KPI fallbacks
        $kpiOpenProjects = (int) (data_get($contact, 'projects_count') ?? data_get($contact, 'kpi_open_projects', 0));
        $kpiUnpaid = data_get($contact, 'unpaid_balance') ?? data_get($contact, 'kpi_unpaid_balance');
        $kpiLastActivity = data_get($contact, 'last_activity_at') ?? data_get($contact, 'kpi_last_activity');
        $kpiNextFollowup = data_get($contact, 'next_followup_at') ?? data_get($contact, 'kpi_next_due');

        // Collections (safe fallbacks)
        $activities = $activities ?? [];
        $projects = $projects ?? [];
        $tasks = $tasks ?? [];
        $billables = $billables ?? [];
        $notes = $notes ?? [];
        $files = $files ?? [];

        $routeOr = function (string $name, array $params = [], string $fallback = '#') {
            return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : $fallback;
        };

        $initials = function ($first, $last, $email) {
            $a = mb_substr(trim((string) $first), 0, 1);
            $b = mb_substr(trim((string) $last), 0, 1);
            if ($a === '' && $b === '' && $email) {
                return mb_strtoupper(mb_substr($email, 0, 1));
            }
            return mb_strtoupper(trim($a . $b)) ?: 'C';
        };

        $money = fn($value) => '$' . number_format((float) $value, 0);
        $emailList = function ($value) {
            if (is_array($value)) {
                return array_values(array_filter($value));
            }
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return array_values(array_filter($decoded));
                }
                return array_values(array_filter(array_map('trim', explode(',', $value))));
            }
            return [];
        };
        $recentEmails = $recentEmails ?? collect();
        $gmailConfigured = $gmailConfigured ?? false;
        $currentMailbox = $currentMailbox ?? null;
    @endphp

    @if (session('success_message') || session('error_message'))
        <div class="mb-4">
            @if (session('magic_link_url'))
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    Magic link: <span class="font-mono text-[11px] break-all">{{ session('magic_link_url') }}</span>
                </div>
            @endif
            @if (session('success_message'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success_message') }}
                </div>
            @endif
            @if (session('error_message'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mt-2">
                    {{ session('error_message') }}
                </div>
            @endif
        </div>
    @endif

    @if (!empty($magicLink) && $magicLinkDaysLeft !== null && $magicLinkDaysLeft <= 2 && $magicLinkDaysLeft >= 0)
        @php
            $expiresAt = $magicLink?->expires_at;
            $secondsLeft = $expiresAt ? max(0, now()->diffInSeconds($expiresAt, false)) : null;
            $interval = $secondsLeft !== null ? \Carbon\CarbonInterval::seconds($secondsLeft)->cascade() : null;
            $parts = [];
            if ($interval) {
                if ($interval->days > 0) {
                    $parts[] = $interval->days . ' day' . ($interval->days === 1 ? '' : 's');
                }
                if ($interval->hours > 0) {
                    $parts[] = $interval->hours . ' hour' . ($interval->hours === 1 ? '' : 's');
                }
                $minutes = max(1, (int) $interval->minutes);
                $parts[] = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
            }
            $countdown = $parts ? implode(' ', $parts) : 'less than a minute';
        @endphp
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    Magic link expires in {{ $countdown }}.
                    Consider sending a new link soon.
                </div>
                <form method="POST"
                    action="{{ $routeOr('tenant.contacts.magic-link-task', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}">
                    @csrf
                    <button type="submit"
                        class="oh-btn inline-flex items-center gap-2 border border-border-default text-text-base hover:bg-surface-accent">
                        <i class="fa-regular fa-square-check text-[12px]"></i>
                        Add Task to Send New Link
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="oh-page max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">People</p>
                <h1 class="text-2xl font-semibold text-text-base">Contact Details</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $routeOr('tenant.contacts.index', ['tenant' => $tenantId], url('/contacts')) }}"
                    class="text-xs text-text-subtle hover:text-text-base inline-flex items-center gap-1 mr-1">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    Back to contacts
                </a>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($email)
                        <a href="mailto:{{ $email }}" class="oh-btn oh-btn--primary inline-flex items-center gap-2">
                            <i class="fa-regular fa-envelope text-[12px]"></i>
                            Email
                        </a>
                    @else
                        <span class="oh-btn opacity-60 cursor-not-allowed inline-flex items-center gap-2" aria-disabled="true">
                            <i class="fa-regular fa-envelope text-[12px]"></i>
                            Email
                        </span>
                    @endif

                    @if ($phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}"
                            class="oh-btn oh-btn--secondary inline-flex items-center gap-2">
                            <i class="fa-solid fa-phone text-[11px]"></i>
                            Call
                        </a>
                    @else
                        <span class="oh-btn oh-btn--secondary opacity-60 cursor-not-allowed inline-flex items-center gap-2" aria-disabled="true">
                            <i class="fa-solid fa-phone text-[11px]"></i>
                            Call
                        </span>
                    @endif

                    <a href="{{ $routeOr('tenant.tasks.create', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}"
                        class="oh-btn oh-btn--primary inline-flex items-center gap-2 opacity-80">
                        <i class="fa-regular fa-square-plus text-[12px]"></i>
                        New Task
                    </a>
                </div>
                <a href="{{ $routeOr('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}"
                    class="oh-btn">
                    Edit contact
                </a>
            </div>
        </header>

        <section class="oh-card p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="h-11 w-11 rounded-xl bg-surface-accent text-text-base ring-1 ring-border-default grid place-items-center text-sm font-semibold">
                        {{ $initials($firstName, $lastName, $email) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-text-base truncate">{{ $fullName }}</div>
                        <div class="text-sm text-text-subtle truncate">
                            {{ $email ?: 'No email on file' }} @if ($phone) • {{ $phone }} @endif
                        </div>
                        <div class="text-xs text-text-subtle truncate">
                            @if ($company)
                                {{ $company }}
                            @else
                                No company on file
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    @php
                        $statusLabel = $invited ? 'Invited' : ucfirst($status);
                        $statusPill = match (true) {
                            $invited => 'oh-pill oh-pill--info',
                            $status === 'active' => 'oh-pill oh-pill--success',
                            default => 'oh-pill oh-pill--muted',
                        };
                    @endphp
                    <span class="{{ $statusPill }}">{{ $statusLabel }}</span>
                    <span class="oh-pill oh-pill--muted">{{ $hasLogin ? 'Portal Access' : 'No Login' }}</span>
                </div>
            </div>
        </section>

        {{-- Stat strip --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="oh-card p-4">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle">Open projects</div>
                <div class="text-base font-semibold text-text-base mt-2">{{ $kpiOpenProjects ?: '—' }}</div>
                @if (!$kpiOpenProjects)
                    <div class="text-xs text-text-subtle mt-1">No data yet</div>
                @endif
            </div>
            <div class="oh-card p-4">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle">Unpaid balance</div>
                <div class="text-base font-semibold text-text-base mt-2">
                    {{ is_null($kpiUnpaid) || $kpiUnpaid == 0 ? '—' : $money($kpiUnpaid) }}
                </div>
                @if (is_null($kpiUnpaid) || $kpiUnpaid == 0)
                    <div class="text-xs text-text-subtle mt-1">No data yet</div>
                @endif
            </div>
            <div class="oh-card p-4">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle">Last activity</div>
                <div class="text-base font-semibold text-text-base mt-2">{{ $kpiLastActivity ?: '—' }}</div>
                @if (!$kpiLastActivity)
                    <div class="text-xs text-text-subtle mt-1">No data yet</div>
                @endif
            </div>
            <div class="oh-card p-4">
                <div class="text-[11px] uppercase tracking-wide text-text-subtle">Next follow-up</div>
                <div class="text-base font-semibold text-text-base mt-2">
                    @if ($kpiNextFollowup)
                        {{ \Illuminate\Support\Carbon::parse($kpiNextFollowup)->format('M j, Y g:ia') }}
                    @else
                        —
                    @endif
                </div>
                @if (!$kpiNextFollowup)
                    <div class="text-xs text-text-subtle mt-1">No data yet</div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            {{-- Left column --}}
            <div class="space-y-5 xl:col-span-2">
                <div class="oh-card p-5">
                    <div class="flex flex-col gap-1 mb-4">
                        <h2 class="text-base font-semibold text-text-base">Engagement</h2>
                    </div>
                    <nav class="tabs__nav flex flex-wrap gap-2 mb-4 text-xs font-medium">
                        <button class="oh-pill oh-pill--info is-active" data-tab="activity">Activity</button>
                        <button class="oh-pill oh-pill--muted" data-tab="projects">Projects</button>
                        <button class="oh-pill oh-pill--muted" data-tab="tasks">Tasks</button>
                        <button class="oh-pill oh-pill--muted" data-tab="billing">Billing</button>
                        <button class="oh-pill oh-pill--muted" data-tab="notes">Notes</button>
                        <button class="oh-pill oh-pill--muted" data-tab="files">Files</button>
                    </nav>

                    {{-- Activity --}}
                    <div class="tabs__panel is-active" id="tab-activity">
                        @if (empty($activities) || count($activities) === 0)
                            <p class="text-sm text-text-subtle">No activity yet.</p>
                        @else
                            <ul class="space-y-2 text-sm">
                                @foreach ($activities as $item)
                                    @php
                                        $type = data_get($item, 'type', 'activity');
                                        $meta = data_get($item, 'meta', []);
                                        $when = \Carbon\Carbon::parse(
                                            data_get($item, 'happened_at', data_get($item, 'created_at', now())),
                                        )->diffForHumans();
                                        $actor = data_get($item, 'actor.name');
                                        $label = match ($type) {
                                            'note.call' => 'Call note added',
                                            'note.meeting' => 'Meeting note added',
                                            'note.decision' => 'Decision noted',
                                            'file.uploaded' => 'File uploaded',
                                            'followup.updated' => 'Follow-up scheduled',
                                            default => ucfirst($type),
                                        };
                                        $link = null;
                                        if (
                                            ($type === 'note.call' ||
                                                $type === 'note.meeting' ||
                                                $type === 'note.decision') &&
                                            data_get($meta, 'note_id')
                                        ) {
                                            $link = '#note-' . data_get($meta, 'note_id');
                                        }
                                    @endphp
                                    <li
                                        class="flex flex-wrap items-center gap-2 border border-border-default rounded-lg px-3 py-2 bg-surface-accent/40">
                                        <span class="oh-pill oh-pill--muted text-[11px]">{{ $label }}</span>
                                        @if ($actor)
                                            <span class="text-text-subtle text-xs">• {{ $actor }}</span>
                                        @endif
                                        <span class="text-text-subtle text-xs">• {{ $when }}</span>
                                        @if ($link)
                                            <a href="{{ $link }}"
                                                class="text-brand-primary hover:text-brand-secondary text-xs">View</a>
                                        @endif
                                        @if ($type === 'file.uploaded' && data_get($meta, 'name'))
                                            <span class="text-xs text-text-subtle truncate">—
                                                {{ data_get($meta, 'name') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Projects --}}
                    <div class="tabs__panel hidden" id="tab-projects">
                        @if (empty($projects))
                            <p class="text-sm text-text-subtle">No projects yet.</p>
                        @else
                            <ul class="space-y-2 text-sm">
                                @foreach ($projects as $project)
                                    @php
                                        $pid = data_get($project, 'id');
                                        $pname =
                                            data_get($project, 'name') ?? data_get($project, 'project_name', 'Project');
                                        $pstatus = data_get($project, 'status', 'open');
                                        $pupdated = data_get($project, 'updated_at');
                                    @endphp
                                    <li
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border border-border-default rounded-xl px-3 py-2 bg-surface-accent/40">
                                        <div class="min-w-0">
                                            <a href="{{ $routeOr('tenant.projects.show', ['tenant' => $tenantId, 'project' => $pid]) }}"
                                                class="font-semibold text-text-base hover:text-brand-primary truncate">
                                                {{ $pname }}
                                            </a>
                                            <div class="text-xs text-text-subtle">
                                                {{ $pupdated ? \Illuminate\Support\Carbon::parse($pupdated)->diffForHumans() : '—' }}
                                            </div>
                                        </div>
                                        <span class="oh-pill oh-pill--muted text-[11px]">{{ ucfirst($pstatus) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Tasks --}}
                    <div class="tabs__panel hidden" id="tab-tasks">
                        @if (empty($tasks))
                            <p class="text-sm text-text-subtle">No tasks yet.</p>
                        @else
                            <ul class="space-y-2 text-sm">
                                @foreach ($tasks as $task)
                                    @php
                                        $tid = data_get($task, 'id');
                                        $ttitle = data_get($task, 'title', 'Task');
                                        $tstatus = data_get($task, 'status', 'open');
                                        $tdue = data_get($task, 'due_date');
                                    @endphp
                                    <li
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border border-border-default rounded-xl px-3 py-2 bg-surface-accent/40">
                                        <div class="min-w-0">
                                            <a href="{{ $routeOr('tenant.tasks.show', ['tenant' => $tenantId, 'task' => $tid]) }}"
                                                class="font-semibold text-text-base hover:text-brand-primary truncate">
                                                {{ $ttitle }}
                                            </a>
                                            <div class="text-xs text-text-subtle">
                                                {{ $tdue ? 'Due ' . \Illuminate\Support\Carbon::parse($tdue)->format('M j, Y') : '—' }}
                                            </div>
                                        </div>
                                        <span class="oh-pill oh-pill--muted text-[11px]">{{ ucfirst($tstatus) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Billing --}}
                    <div class="tabs__panel hidden" id="tab-billing">
                        <p class="text-sm text-text-subtle">Coming soon.</p>
                    </div>

                    {{-- Notes --}}
                    <div class="tabs__panel hidden" id="tab-notes">
                        <form class="space-y-2" method="POST"
                            action="{{ $routeOr('contacts.notes.store', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}">
                            @csrf
                            <textarea name="body" required minlength="8" maxlength="2000"
                                class="oh-textarea w-full"
                                rows="3" placeholder="Context → Decision → Next step"></textarea>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
                                <select name="note_type" class="oh-select">
                                    <option value="">Note type (optional)</option>
                                    @foreach (['Call', 'Meeting', 'Decision', 'Preference', 'Risk', 'General'] as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                <input type="datetime-local" name="happened_at"
                                    class="oh-input"
                                    placeholder="When it happened (optional)">
                                <label class="inline-flex items-center gap-2 text-xs text-text-subtle">
                                    <input type="checkbox" name="pinned" value="1"
                                        class="rounded border-border-default">
                                    Pin note
                                </label>
                            </div>
                            <button type="submit" class="oh-btn oh-btn--primary text-sm">Save note</button>
                        </form>

                        <div class="mt-4 space-y-3">
                            @forelse ($notes as $note)
                                <div class="border border-border-default/70 rounded-xl px-3 py-2 bg-surface-accent"
                                    id="note-{{ data_get($note, 'id') }}">
                                    <div class="flex items-center justify-between gap-2 text-xs text-text-subtle mb-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if (data_get($note, 'note_type'))
                                                <span class="oh-pill oh-pill--muted text-[11px]">
                                                    {{ data_get($note, 'note_type') }}
                                                </span>
                                            @endif
                                            <span>{{ \Carbon\Carbon::parse(data_get($note, 'created_at', now()))->diffForHumans() }}</span>
                                            @if (data_get($note, 'author.name'))
                                                • {{ data_get($note, 'author.name') }}
                                            @endif
                                            @if (data_get($note, 'pinned'))
                                                <span class="oh-pill oh-pill--info text-[10px]">Pinned</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <form method="POST"
                                                action="{{ $routeOr('contacts.notes.pin', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id'), 'note' => data_get($note, 'id')]) }}">
                                                @csrf
                                                <button type="submit" class="oh-icon-btn oh-tooltip"
                                                    data-tooltip="Pin/unpin">
                                                    <i class="fa-solid fa-thumbtack text-[11px]"></i>
                                                </button>
                                            </form>
                                            <form method="POST"
                                                action="{{ $routeOr('contacts.notes.destroy', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id'), 'note' => data_get($note, 'id')]) }}"
                                                onsubmit="return confirm('Delete this note?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="oh-icon-btn oh-tooltip"
                                                    data-tooltip="Delete note">
                                                    <i class="fa-solid fa-trash text-[11px] text-rose-500"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="text-sm whitespace-pre-line text-text-base line-clamp-3" data-expandable>
                                        {{ data_get($note, 'body', '') }}
                                    </div>
                                    @if (data_get($note, 'happened_at'))
                                        <div class="text-[11px] text-text-subtle mt-1">
                                            Happened
                                            {{ \Carbon\Carbon::parse(data_get($note, 'happened_at'))->format('M j, Y g:ia') }}
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between mt-2 text-xs">
                                        <button type="button"
                                            class="oh-btn text-xs px-2 py-1 toggle-expand">Expand</button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-text-subtle">No notes yet.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Files --}}
                    <div class="tabs__panel hidden" id="tab-files">
                        @php
                            $categories = ['Contract/SOW', 'Billing', 'Access', 'Brand', 'Requirements', 'Other'];
                        @endphp
                        <form class="space-y-2" method="POST" enctype="multipart/form-data"
                            action="{{ $routeOr('contacts.files.store', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                <select name="category" class="oh-select" required>
                                    <option value="">Select category</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="description"
                                    class="oh-input"
                                    placeholder="Description (optional)">
                            </div>
                            <button type="submit" class="oh-btn oh-btn--primary text-sm">Upload file</button>
                        </form>

                        <div class="mt-4 space-y-2 text-sm">
                            @forelse ($files as $file)
                                <div
                                    class="flex items-start justify-between gap-3 border border-border-default rounded-xl px-3 py-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span
                                                class="oh-pill oh-pill--muted text-[11px]">{{ data_get($file, 'category', 'File') }}</span>
                                            <span class="text-xs text-text-subtle">
                                                {{ \Carbon\Carbon::parse(data_get($file, 'created_at', now()))->format('M j, Y') }}
                                            </span>
                                            @if (data_get($file, 'uploaded_by'))
                                                <span class="text-xs text-text-subtle">•
                                                    {{ data_get($file, 'uploader.name') }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ $routeOr('contacts.files.download', ['tenant' => $tenantId, 'file' => data_get($file, 'id')]) }}"
                                            class="block text-brand-primary hover:text-brand-secondary truncate">
                                            {{ data_get($file, 'original_name', 'File') }}
                                        </a>
                                        @if (data_get($file, 'description'))
                                            <div class="text-xs text-text-subtle truncate">
                                                {{ data_get($file, 'description') }}</div>
                                        @endif
                                        @if (data_get($file, 'size'))
                                            <div class="text-[11px] text-text-subtle">
                                                {{ number_format(data_get($file, 'size') / 1024, 1) }} KB
                                            </div>
                                        @endif
                                    </div>
                                    <form method="POST"
                                        action="{{ $routeOr('contacts.files.destroy', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id'), 'file' => data_get($file, 'id')]) }}"
                                        onsubmit="return confirm('Delete this file?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="oh-icon-btn oh-tooltip" data-tooltip="Delete file">
                                            <i class="fa-solid fa-trash text-[11px] text-rose-500"></i>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-text-subtle">No files uploaded yet. Use categories like Contract/SOW
                                    or
                                    Billing.</p>
                            @endforelse
                        </div>
                        @if ($company)
                            <p class="text-xs text-text-subtle mt-3">
                                Tip: Contract/SOW or Billing documents often belong on the company. Upload here if
                                contact-specific,
                                or
                                <a href="{{ $routeOr('tenant.companies.show', ['tenant' => $tenantId, 'company' => $companyId]) }}"
                                    class="text-brand-primary hover:text-brand-secondary">open Company files</a>.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right rail --}}
            <aside class="space-y-4 xl:col-span-1">
                <div class="oh-card p-4">
                    <h3 class="text-sm font-semibold text-text-base mb-1">Next follow-up</h3>
                    <p class="text-xs text-text-subtle mb-3">Pick a date to keep the follow-up on track.</p>
                    @php
                        $isOverdue = $kpiNextFollowup && \Illuminate\Support\Carbon::parse($kpiNextFollowup)->isPast();
                    @endphp
                    <div class="flex items-center gap-2 text-sm">
                        <span>{{ $kpiNextFollowup ? \Illuminate\Support\Carbon::parse($kpiNextFollowup)->format('M j, Y g:ia') : 'Not scheduled' }}</span>
                        @if ($isOverdue)
                            <span class="oh-pill oh-pill--danger text-[11px]">Overdue</span>
                        @elseif ($kpiNextFollowup)
                            <span class="oh-pill oh-pill--muted text-[11px]">
                                In {{ \Illuminate\Support\Carbon::parse($kpiNextFollowup)->diffForHumans(null, true) }}
                            </span>
                        @endif
                    </div>
                    <form class="mt-3 space-y-2" method="POST"
                        action="{{ $routeOr('contacts.followup', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}">
                        @csrf
                        <label class="text-xs text-text-subtle" for="next_followup_at">Set follow-up</label>
                        <input type="datetime-local" id="next_followup_at" name="next_followup_at"
                            value="{{ $kpiNextFollowup ? \Illuminate\Support\Carbon::parse($kpiNextFollowup)->format('Y-m-d\\TH:i') : '' }}"
                            class="oh-input w-full">
                        <div class="flex items-center gap-2">
                            <button type="submit" class="oh-btn oh-btn--primary w-full text-sm">Save follow-up</button>
                            @if ($kpiNextFollowup)
                                <a href="{{ $routeOr('contacts.followup', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}"
                                    class="text-xs text-text-subtle hover:text-text-base whitespace-nowrap">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="oh-card p-4">
                    <h3 class="text-sm font-semibold text-text-base mb-1">Login details</h3>
                    <p class="text-xs text-text-subtle mb-3">Portal access and authentication.</p>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-text-subtle">Magic link access</span>
                            @if ($hasLogin)
                                <form method="POST"
                                    action="{{ $routeOr('tenant.contacts.magic-link', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}">
                                    @csrf
                                    <button type="submit"
                                        class="oh-btn inline-flex items-center gap-2 text-xs">
                                        <i class="fa-solid fa-link text-[11px]"></i>
                                        Send magic link
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-text-subtle">Create a login first</span>
                            @endif
                        </div>
                        @if (session('magic_link_url'))
                            <div class="flex items-center gap-2">
                                <input type="text" readonly value="{{ session('magic_link_url') }}"
                                    class="oh-input w-full text-[11px]" aria-label="Magic link URL">
                                <button type="button"
                                    class="oh-btn text-xs"
                                    onclick="navigator.clipboard?.writeText('{{ session('magic_link_url') }}')">
                                    Copy
                                </button>
                            </div>
                        @endif
                        @if (!empty($magicLink) && $magicLink?->expires_at)
                            <div class="text-xs text-text-subtle">
                                Active link expires {{ \Illuminate\Support\Carbon::parse($magicLink->expires_at)->diffForHumans() }}.
                            </div>
                        @endif
                    </div>

                    <div class="mt-4 space-y-2">
                        @if (!$hasLogin)
                            <form method="POST"
                                action="{{ $routeOr('tenant.contacts.create-login', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}"
                                class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="email" name="email" value="{{ $email }}" required
                                    class="oh-input w-full sm:w-auto"
                                    placeholder="Client email">
                                <button type="submit" class="oh-btn inline-flex items-center gap-2">
                                    <i class="fa fa-key text-xs"></i>
                                    Create login
                                </button>
                            </form>
                        @else
                            <form method="POST"
                                action="{{ $routeOr('tenant.contacts.resend-login', ['tenant' => $tenantId, 'contact' => data_get($contact, 'id')]) }}"
                                class="flex flex-wrap items-center gap-2">
                                @csrf
                                <button type="submit" class="oh-btn inline-flex items-center gap-2">
                                    <i class="fa fa-envelope text-xs"></i>
                                    Resend login email
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-text-base">Recent emails</h3>
                        @if ($gmailConfigured && $currentMailbox && $currentMailbox->status === 'connected')
                            <form method="POST"
                                action="{{ $routeOr('tenant.settings.mailbox.sync', ['tenant' => $tenantId]) }}">
                                @csrf
                                <button type="submit"
                                    class="oh-btn oh-btn--primary text-xs {{ $currentMailbox->sync_in_progress ? 'opacity-60 cursor-not-allowed' : '' }}"
                                    {{ $currentMailbox->sync_in_progress ? 'disabled' : '' }}>
                                    {{ $currentMailbox->sync_in_progress ? 'Sync in progress' : 'Sync now' }}
                                </button>
                            </form>
                        @endif
                    </div>

                    @if (!$gmailConfigured)
                        <p class="text-sm text-text-subtle">
                            Gmail sync is not configured. Configure it in
                            <a href="{{ $routeOr('tenant.settings.mailbox', ['tenant' => $tenantId]) }}"
                                class="text-brand-primary hover:text-brand-secondary">Mailbox Sync</a>.
                        </p>
                    @elseif (!$currentMailbox || $currentMailbox->status !== 'connected')
                        <p class="text-sm text-text-subtle">
                            Connect your mailbox to see recent emails.
                            <a href="{{ $routeOr('tenant.settings.mailbox', ['tenant' => $tenantId]) }}"
                                class="text-brand-primary hover:text-brand-secondary">Connect Gmail</a>.
                        </p>
                    @elseif ($recentEmails->isEmpty())
                        <p class="text-sm text-text-subtle">No recent emails yet.</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ $routeOr('tenant.email-logs.index', ['tenant' => $tenantId]) }}"
                                class="oh-btn text-xs">Open email log</a>
                            <form method="POST"
                                action="{{ $routeOr('tenant.settings.mailbox.sync', ['tenant' => $tenantId]) }}">
                                @csrf
                                <button type="submit"
                                    class="oh-btn oh-btn--primary text-xs {{ $currentMailbox->sync_in_progress ? 'opacity-60 cursor-not-allowed' : '' }}"
                                    {{ $currentMailbox->sync_in_progress ? 'disabled' : '' }}>
                                    {{ $currentMailbox->sync_in_progress ? 'Sync in progress' : 'Sync now' }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentEmails as $log)
                                @php
                                    $to = $emailList(data_get($log, 'to_emails'));
                                    $cc = $emailList(data_get($log, 'cc_emails'));
                                    $direction = data_get($log, 'direction', 'inbound');
                                    $sentAt = data_get($log, 'sent_at') ? \Illuminate\Support\Carbon::parse(data_get($log, 'sent_at')) : null;
                                @endphp
                                <div class="border border-border-default rounded-lg p-3 space-y-1">
                                    <div class="flex items-center gap-2 text-xs">
                                        <span
                                            class="oh-pill {{ $direction === 'outbound' ? 'oh-pill--info' : 'oh-pill--muted' }}">
                                            {{ ucfirst($direction) }}
                                        </span>
                                        @if (data_get($log, 'status') === 'needs_review')
                                            <span class="oh-pill oh-pill--warning">Needs review</span>
                                        @endif
                                        @if ($sentAt)
                                            <span class="text-text-subtle">{{ $sentAt->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <div class="text-sm font-semibold text-text-base truncate">
                                        {{ data_get($log, 'subject', '(No subject)') }}
                                    </div>
                                    <div class="text-xs text-text-subtle truncate">
                                        <span class="font-semibold text-text-base">{{ data_get($log, 'from_email') }}</span>
                                        @if (!empty($to))
                                            <span class="text-text-subtle">→ {{ implode(', ', array_slice($to, 0, 2)) }}</span>
                                        @endif
                                        @if (!empty($cc))
                                            <span class="text-text-subtle"> • cc: {{ implode(', ', array_slice($cc, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-text-subtle line-clamp-2">
                                        {{ data_get($log, 'snippet') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ $routeOr('tenant.email-logs.index', ['tenant' => $tenantId]) }}"
                            class="oh-btn text-sm w-full justify-center mt-2">View all email logs</a>
                    @endif
                </div>

            </aside>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navBtns = document.querySelectorAll('.tabs__nav button');
            const panels = document.querySelectorAll('.tabs__panel');
            const expandToggles = document.querySelectorAll('.toggle-expand');

            navBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.dataset.tab;
                    navBtns.forEach(b => {
                        b.classList.remove('is-active');
                        b.classList.remove('oh-pill--info');
                        b.classList.add('oh-pill--muted');
                    });
                    panels.forEach(p => p.classList.add('hidden'));
                    btn.classList.add('is-active');
                    btn.classList.add('oh-pill--info');
                    btn.classList.remove('oh-pill--muted');
                    const panel = document.getElementById('tab-' + target);
                    if (panel) panel.classList.remove('hidden');
                });
            });

            expandToggles.forEach(btn => {
                btn.addEventListener('click', () => {
                    const container = btn.closest('[data-expandable]') || btn
                        .previousElementSibling;
                    if (!container) return;
                    container.classList.toggle('line-clamp-3');
                    btn.textContent = container.classList.contains('line-clamp-3') ? 'Expand' :
                        'Collapse';
                });
            });
        });
    </script>


@endsection
