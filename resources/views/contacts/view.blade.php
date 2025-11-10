@extends('layouts.app')

@section('title', 'Contact')

@section('content')
    @php
        // Expecting: $client (Client model), $tenant (id), plus optional arrays:
        // $projects_active, $projects_recent, $tasks_assigned, $invoices_unpaid,
        // $aging, $notes_recent, $files_recent, $other_contacts, $activities, $kpi_*
        $tenantId = $tenant ?? (auth()->user()->tenant_id ?? null);

        // Safeguards
        $fullName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''));
        $company = $client->company_name ?? null;
        $status = strtolower($client->status ?? 'active');
        $hasLogin = property_exists($client, 'has_login')
            ? (bool) $client->has_login
            : (bool) optional($client->userAccount)->exists;
        $money = fn($n) => '$' . number_format((float) $n, 0);

        // KPI inputs
        $kpi = [
            'open_projects' => (int) ($kpi_open_projects ?? 0),
            'unpaid' => (float) ($kpi_unpaid_balance ?? 0),
            'last_activity' => $kpi_last_activity ?? null,
            'next_due' => $kpi_next_due ?? null,
            'last_invoice' => $kpi_last_invoice ?? null,
        ];

        // Collections/arrays (graceful defaults)
        $projects_active = $projects_active ?? [];
        $projects_recent = $projects_recent ?? [];
        $tasks_assigned = $tasks_assigned ?? [];
        $invoices_unpaid = $invoices_unpaid ?? [];
        $aging = $aging ?? ['0-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $notes_recent = $notes_recent ?? [];
        $files_recent = $files_recent ?? [];
        $other_contacts = $other_contacts ?? [];
        $activities = $activities ?? [];

        // Route helpers with safe fallbacks (in case some features aren’t routed yet)
        $routeOr = function ($name, $params = [], $fallback = '#') {
            return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : $fallback;
        };
    @endphp

    <section class="page-head mb-4">
        <a href="{{ $routeOr('tenant.contacts.index', ['tenant' => $tenantId], url('/contacts')) }}" class="btn btn--ghost">
            <i class="fa fa-arrow-left"></i> Back to Contacts
        </a>
    </section>

    <div class="client-layout grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LEFT: Context --}}
        <aside class="client-sidebar space-y-6 lg:col-span-1">
            <div class="client-card bg-white rounded-xl shadow p-5">
                <h3 class="text-lg font-semibold mb-3">Contact Details</h3>
                <dl class="details text-sm space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Email</dt>
                        <dd>
                            <a class="text-blue-600 hover:underline"
                                href="mailto:{{ $client->email }}">{{ $client->email ?? '—' }}</a>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Phone</dt>
                        <dd>{{ $client->phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Company</dt>
                        <dd>{{ $company ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Status</dt>
                        <dd class="capitalize">{{ $status }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Account</dt>
                        <dd>{{ $hasLogin ? 'Created' : 'Not created' }}</dd>
                    </div>
                </dl>

                @unless ($hasLogin)
                    <div class="mt-4">
                        <a class="btn btn--ghost" href="{{ url('/admins/create-client-user', ['client_id' => $client->id]) }}">
                            <i class="fa fa-key"></i> Create login
                        </a>
                    </div>
                @endunless
            </div>

            @if (!empty($other_contacts))
                <div class="client-card bg-white rounded-xl shadow p-5">
                    <h3 class="text-lg font-semibold mb-3">Other Contacts</h3>
                    <ul class="simple-list space-y-2 text-sm">
                        @foreach ($other_contacts as $oc)
                            <li>
                                <a class="text-blue-600 hover:underline"
                                    href="{{ $routeOr('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $oc['id'] ?? null], url('/contacts/view/' . ($oc['id'] ?? ''))) }}">
                                    {{ $oc['name'] ?? 'Contact' }}
                                </a>
                                — <span class="text-gray-600">{{ $oc['email'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="client-card bg-white rounded-xl shadow p-5">
                <h3 class="text-lg font-semibold mb-3">Notes</h3>

                @if (empty($notes_recent))
                    <p class="text-gray-500 text-sm">No notes yet.</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($notes_recent as $n)
                            <li class="border border-gray-200 rounded-lg p-3">
                                <div class="note__meta text-xs text-gray-500 mb-1">
                                    {{ \Carbon\Carbon::parse($n['created_at'] ?? now())->format('M j, Y g:ia') }}
                                    • {{ $n['author'] ?? '' }}
                                </div>
                                <div class="note__body text-sm whitespace-pre-line">
                                    {{ $n['body'] ?? '' }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- TODO: Wire this to a proper notes store route if/when you add it --}}
                <form method="POST" action="#" class="note-add mt-3 opacity-50 pointer-events-none">
                    @csrf
                    <textarea name="body" rows="3" placeholder="Add a quick note…" class="w-full border rounded p-2"></textarea>
                    <button class="btn btn--brand btn--sm mt-2" type="button" disabled>Add Note</button>
                </form>
            </div>

            <div class="client-card bg-white rounded-xl shadow p-5">
                <h3 class="text-lg font-semibold mb-3">Files</h3>
                @if (empty($files_recent))
                    <p class="text-gray-500 text-sm">No files uploaded.</p>
                @else
                    <ul class="simple-list space-y-2 text-sm">
                        @foreach ($files_recent as $f)
                            <li>
                                <a class="text-blue-600 hover:underline" href="{{ $f['url'] ?? '#' }}" target="_blank"
                                    rel="noopener">
                                    {{ $f['name'] ?? 'File' }}
                                </a>
                                <span class="text-gray-500"> • {{ $f['size'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a class="btn btn--ghost btn--sm mt-3" href="{{ url('/files/upload', ['client_id' => $client->id]) }}">
                    <i class="fa fa-upload"></i> Upload
                </a>
            </div>
        </aside>

        {{-- RIGHT: Main --}}
        <section class="client-main lg:col-span-2 space-y-6">
            {{-- Hero --}}
            <div class="client-hero bg-white rounded-xl shadow p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="client-hero__title">
                        <div class="flex items-center gap-3">
                            <div
                                class="avatar avatar--lg w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-lg font-semibold">
                                {{ mb_strtoupper(mb_substr($fullName ?: 'C', 0, 1)) }}
                            </div>
                            <div>
                                <h1 class="text-xl font-semibold">{{ $fullName ?: '—' }}</h1>
                                <div class="client-hero__chips mt-1 space-x-2">
                                    <span
                                        class="chip chip--ok inline-block px-2 py-0.5 rounded bg-green-100 text-green-700 text-xs">{{ $status }}</span>
                                    @if (!$hasLogin)
                                        <span
                                            class="chip chip--muted inline-block px-2 py-0.5 rounded bg-gray-200 text-gray-700 text-xs">no
                                            login</span>
                                    @else
                                        <span
                                            class="chip chip--brand inline-block px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-xs">
                                            <i class="fa-solid fa-key mr-1"></i>login
                                        </span>
                                    @endif
                                    @if (!empty($company))
                                        <span
                                            class="chip chip--brand inline-block px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-xs">
                                            <i class="fa-solid fa-building mr-1"></i>{{ $company }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="client-hero__actions flex flex-wrap gap-2">
                        <a class="btn btn--brand"
                            href="{{ $routeOr('tenant.projects.create', ['tenant' => $tenantId, 'client_id' => $client->id], url('/projects/create?client_id=' . $client->id)) }}">
                            + New Project
                        </a>
                        <a class="btn btn--ghost"
                            href="{{ $routeOr('tenant.tasks.create', ['tenant' => $tenantId], url('/tasks/assign?client_id=' . $client->id)) }}">
                            New Task
                        </a>
                        <a class="btn btn--ghost"
                            href="{{ $routeOr('tenant.invoices.create', ['tenant' => $tenantId, 'client_id' => $client->id], url('/invoices/create?client_id=' . $client->id)) }}">
                            New Invoice
                        </a>
                        <a class="btn btn--ghost" href="mailto:{{ $client->email }}">Email</a>
                        <a class="btn btn--ghost"
                            href="{{ $routeOr('tenant.contacts.edit', ['tenant' => $tenantId, 'contact' => $client->id], url('/contacts/edit/' . $client->id)) }}">
                            Edit
                        </a>
                    </div>
                </div>
            </div>

            {{-- KPI strip --}}
            <div class="client-kpis grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-xs text-gray-500">Open Projects</div>
                    <div class="text-2xl font-bold">{{ (int) $kpi['open_projects'] }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-xs text-gray-500">Unpaid Balance</div>
                    <div class="text-2xl font-bold">{{ $money($kpi['unpaid']) }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-xs text-gray-500">Last Activity</div>
                    <div class="text-lg">{{ $kpi['last_activity'] ?: '—' }}</div>
                </div>
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="text-xs text-gray-500">Next Task Due</div>
                    <div class="text-lg">{{ $kpi['next_due'] ?: '—' }}</div>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="client-card bg-white rounded-xl shadow p-5">
                <nav class="tabs__nav flex gap-3 mb-4 text-sm">
                    <button class="is-active px-3 py-1 rounded bg-gray-100" data-tab="projects">Projects</button>
                    <button class="px-3 py-1 rounded hover:bg-gray-100" data-tab="activity">Activity</button>
                    <button class="px-3 py-1 rounded hover:bg-gray-100" data-tab="billing">Billing</button>
                    <button class="px-3 py-1 rounded hover:bg-gray-100" data-tab="tasks">Tasks</button>
                </nav>

                {{-- Projects --}}
                <div class="tabs__panel is-active" id="tab-projects">
                    @if (empty($projects_active) && empty($projects_recent))
                        <p class="text-gray-500">No projects yet.</p>
                        <a class="btn btn--brand btn--sm mt-2"
                            href="{{ $routeOr('tenant.projects.create', ['tenant' => $tenantId, 'client_id' => $client->id], url('/projects/create?client_id=' . $client->id)) }}">
                            <i class="fa fa-plus"></i> Create Project
                        </a>
                    @else
                        <h4 class="font-semibold mb-2">Active</h4>
                        <ul class="grid sm:grid-cols-2 gap-3">
                            @foreach ($projects_active as $p)
                                @php
                                    $pid = is_array($p) ? $p['id'] ?? null : $p->id ?? null;
                                    $pname = is_array($p)
                                        ? $p['name'] ?? ''
                                        : $p->name ?? ($p->project_name ?? 'Project');
                                    $pstatus = is_array($p) ? $p['status'] ?? 'open' : $p->status ?? 'open';
                                    $pstart = is_array($p) ? $p['start_date'] ?? null : $p->start_date ?? null;
                                    $ppct = (int) (is_array($p) ? $p['progress_pct'] ?? 0 : $p->progress_pct ?? 0);
                                @endphp
                                <li class="tile border border-gray-200 rounded-lg p-3">
                                    <div class="tile__title">
                                        <a class="text-blue-600 hover:underline"
                                            href="{{ $routeOr('tenant.projects.show', ['tenant' => $tenantId, 'project' => $pid], url('/projects/view/' . $pid)) }}">
                                            {{ $pname }}
                                        </a>
                                    </div>
                                    <div class="tile__meta text-xs text-gray-500">
                                        {{ $pstatus }} •
                                        {{ $pstart ? \Carbon\Carbon::parse($pstart)->format('M j, Y') : '—' }}
                                    </div>
                                    <div class="progress h-2 bg-gray-200 rounded mt-2">
                                        <span class="block h-2 bg-blue-500 rounded"
                                            style="width: {{ $ppct }}%"></span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        @if (!empty($projects_recent))
                            <h4 class="font-semibold mt-6 mb-2">Recently Updated</h4>
                            <ul class="simple-list space-y-1 text-sm">
                                @foreach ($projects_recent as $p)
                                    @php
                                        $pid = is_array($p) ? $p['id'] ?? null : $p->id ?? null;
                                        $pname = is_array($p)
                                            ? $p['name'] ?? ''
                                            : $p->name ?? ($p->project_name ?? 'Project');
                                        $pupd = is_array($p) ? $p['updated_at'] ?? null : $p->updated_at ?? null;
                                    @endphp
                                    <li>
                                        <a class="text-blue-600 hover:underline"
                                            href="{{ $routeOr('tenant.projects.show', ['tenant' => $tenantId, 'project' => $pid], url('/projects/view/' . $pid)) }}">
                                            {{ $pname }}
                                        </a>
                                        <span class="text-gray-500">— updated
                                            {{ $pupd ? \Carbon\Carbon::parse($pupd)->format('M j') : '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endif
                </div>

                {{-- Activity --}}
                <div class="tabs__panel hidden" id="tab-activity">
                    @if (empty($activities))
                        <p class="text-gray-500">No recent activity.</p>
                    @else
                        <ul class="timeline space-y-2 text-sm">
                            @foreach ($activities as $a)
                                @php
                                    $type = ucfirst($a['type'] ?? 'Activity');
                                    $label = $a['label'] ?? '';
                                    $when = $a['when'] ?? '';
                                    $href = $a['href'] ?? null;
                                @endphp
                                <li>
                                    <span
                                        class="badge inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">{{ $type }}</span>
                                    <span class="ml-2">{{ $label }}</span>
                                    <span class="text-gray-500 ml-1">• {{ $when }}</span>
                                    @if ($href)
                                        <a class="text-blue-600 hover:underline ml-2" href="{{ $href }}">View</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Billing --}}
                <div class="tabs__panel hidden" id="tab-billing">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold mb-2">Unpaid Invoices</h4>
                            @if (empty($invoices_unpaid))
                                <p class="text-gray-500 text-sm">No unpaid invoices.</p>
                            @else
                                <ul class="simple-list space-y-1 text-sm">
                                    @foreach ($invoices_unpaid as $inv)
                                        @php
                                            $iid = $inv['id'] ?? null;
                                            $inum = $inv['number'] ?? $iid;
                                            $due = $inv['due_date'] ?? null;
                                            $amt = $inv['amount'] ?? 0;
                                        @endphp
                                        <li>
                                            <a class="text-blue-600 hover:underline"
                                                href="{{ url('/invoices/view/' . $iid) }}">#{{ $inum }}</a>
                                            — due {{ $due ? \Carbon\Carbon::parse($due)->format('M j, Y') : '—' }}
                                            <strong class="ml-1">{{ $money($amt) }}</strong>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">AR Aging</h4>
                            <ul class="space-y-1 text-sm text-gray-700">
                                <li>0–30: <strong>{{ $money($aging['0-30'] ?? 0) }}</strong></li>
                                <li>31–60: <strong>{{ $money($aging['31-60'] ?? 0) }}</strong></li>
                                <li>61–90: <strong>{{ $money($aging['61-90'] ?? 0) }}</strong></li>
                                <li>90+: <strong>{{ $money($aging['90+'] ?? 0) }}</strong></li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a class="btn btn--brand btn--sm"
                            href="{{ $routeOr('tenant.invoices.create', ['tenant' => $tenantId, 'client_id' => $client->id], url('/invoices/create?client_id=' . $client->id)) }}">
                            <i class="fa fa-file-invoice"></i> New Invoice
                        </a>
                    </div>
                </div>

                {{-- Tasks --}}
                <div class="tabs__panel hidden" id="tab-tasks">
                    @if (empty($tasks_assigned))
                        <p class="text-gray-500 text-sm">No tasks assigned.</p>
                    @else
                        <ul class="simple-list space-y-1 text-sm">
                            @foreach ($tasks_assigned as $t)
                                @php
                                    $tid = $t['id'] ?? null;
                                    $ttitle = $t['title'] ?? 'Task';
                                    $tstatus = $t['status'] ?? 'open';
                                    $tdue = $t['due_date'] ?? null;
                                @endphp
                                <li>
                                    <a class="text-blue-600 hover:underline"
                                        href="{{ $routeOr('tenant.tasks.index', ['tenant' => $tenantId], url('/tasks')) }}#task-{{ (int) $tid }}">
                                        {{ $ttitle }}
                                    </a>
                                    — <span
                                        class="badge inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs">
                                        {{ $tstatus }}
                                    </span>
                                    <span class="text-gray-500 ml-1">
                                        due {{ $tdue ? \Carbon\Carbon::parse($tdue)->format('M j') : '—' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <a class="btn btn--ghost btn--sm mt-2"
                        href="{{ $routeOr('tenant.tasks.create', ['tenant' => $tenantId], url('/tasks/assign?client_id=' . $client->id)) }}">
                        <i class="fa fa-plus"></i> Add Task
                    </a>
                </div>
            </div>
        </section>
    </div>

    {{-- Tiny tabs toggler (no framework required) --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const navBtns = document.querySelectorAll('.tabs__nav button');
            const panels = document.querySelectorAll('.tabs__panel');
            navBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    navBtns.forEach(b => b.classList.remove('is-active', 'bg-gray-100'));
                    panels.forEach(p => p.classList.add('hidden'));
                    btn.classList.add('is-active', 'bg-gray-100');
                    const id = 'tab-' + btn.dataset.tab;
                    document.getElementById(id)?.classList.remove('hidden');
                });
            });
        });
    </script>
@endsection
