@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
    <div class="oh-page">
        @php
            $currentUser = auth()->user();
            $role = strtolower((string) ($currentUser?->role ?? ''));
            $canDelete = in_array($role, ['provider', 'admin', 'super_admin', 'superadmin'], true) ||
                ((int) ($task->user_id ?? 0) === (int) ($currentUser?->id ?? 0));

            $taskStatus = strtolower((string) ($task->status ?? 'todo'));
            $statusPill = match ($taskStatus) {
                'completed' => 'oh-pill--success',
                'in_progress', 'in progress' => 'oh-pill--info',
                'blocked' => 'oh-pill--danger',
                default => 'oh-pill--muted',
            };
            $dueBadge = function ($date) {
                if (!$date) {
                    return ['label' => 'No due date', 'class' => 'oh-pill oh-pill--muted'];
                }
                $today = now()->startOfDay();
                $due = \Carbon\Carbon::parse($date)->startOfDay();
                if ($due->lt($today)) {
                    return ['label' => $due->format('M d, Y'), 'class' => 'oh-pill oh-pill--danger'];
                }
                if ($due->isSameDay($today)) {
                    return ['label' => $due->format('M d, Y'), 'class' => 'oh-pill oh-pill--accent'];
                }
                return ['label' => $due->format('M d, Y'), 'class' => 'oh-pill oh-pill--brand'];
            };
            $duePill = $dueBadge($task->due_date ?? null);

            $assignType = strtolower((string) ($task->assign_type ?? ''));
            $assignedUserName =
                $assignType === 'admin'
                    ? (optional($task->teamMember)->fullName
                        ?: (optional($task->user)->display_name ?? optional($task->user)->name ?? '—'))
                    : '—';
            $assignedClientName = trim(
                ($task->client?->firstName ?? $task->client?->first_name ?? '') .
                    ' ' .
                    ($task->client?->lastName ?? $task->client?->last_name ?? ''),
            );
            $assignedClientName = $assignedClientName !== '' ? $assignedClientName : '—';
            if ($assignedClientName === '—' && $assignType === 'client') {
                $clientId = $task->assign_id ?? $task->contact_id ?? null;
                if ($clientId) {
                    $clientModel = \App\Models\Client::find($clientId);
                    $assignedClientName = trim(
                        ($clientModel?->firstName ?? $clientModel?->first_name ?? '') .
                            ' ' .
                            ($clientModel?->lastName ?? $clientModel?->last_name ?? ''),
                    );
                    $assignedClientName = $assignedClientName !== '' ? $assignedClientName : '—';
                }
            }
            $tenantTz = $tenant->timezone ?? 'America/Denver';
            $scheduleAssignee = trim(
                ($task->assignedUser?->first_name ?? '') . ' ' . ($task->assignedUser?->last_name ?? '')
            );
            $scheduleAssignee = $scheduleAssignee !== '' ? $scheduleAssignee : ($task->assignedUser?->email ?? '—');
        @endphp
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs text-text-subtle">Task</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $task->title }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-text-subtle">
                    <span class="oh-pill {{ $statusPill }}">
                        {{ ucfirst($task->status ?? 'todo') }}
                    </span>
                    <span class="{{ $duePill['class'] }}">
                        <i class="fa-regular fa-calendar"></i>
                        Due {{ $duePill['label'] }}
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant]) }}" class="oh-btn">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    All Tasks
                </a>
                <a href="{{ route('tenant.tasks.edit', ['tenant' => $tenant, 'task' => $task->id]) }}" class="oh-btn"
                    title="Edit task" aria-label="Edit task">
                    <i class="fa-solid fa-pen text-xs"></i> Edit
                </a>
                @if ($canDelete)
                    <form method="POST"
                        action="{{ route('tenant.tasks.destroy', ['tenant' => $tenant, 'task' => $task->id]) }}"
                        onsubmit="return confirm('Delete this task? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-btn" title="Delete task" aria-label="Delete task">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @php
            $contactId = $task->contact_id ?? null;
            $contactName = trim(
                ($task->client?->firstName ?? $task->client?->first_name ?? '') .
                ' ' .
                ($task->client?->lastName ?? $task->client?->last_name ?? '')
            );
            $contactName = $contactName !== '' ? $contactName : 'this client';
            $isMagicLinkTask = \Illuminate\Support\Str::startsWith($task->title ?? '', 'Send new portal link to ');
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <div class="md:col-span-7 space-y-4">
                <div class="oh-card p-5">
                    <h2 class="text-sm font-semibold text-text-base">Details</h2>
                    <dl class="mt-3 grid gap-2 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Project</dt>
                            <dd class="font-semibold text-text-base">{{ $task->project?->project_name ?? '—' }}</dd>
                        </div>
                        @php
                            $projectClientCompany =
                                $task->project?->contact?->company_name ?? $task->project?->contact?->company?->company_name;
                        @endphp
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Client company</dt>
                            <dd class="font-semibold text-text-base">{{ $projectClientCompany ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Project client</dt>
                            <dd class="font-semibold text-text-base">
                                {{ $task->project?->contact?->fullName
                                    ?? $task->project?->client?->fullName
                                    ?? $projectClientCompany
                                    ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Assigned team member</dt>
                            <dd class="font-semibold text-text-base">{{ $assignedUserName }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Assigned Client</dt>
                            <dd class="font-semibold text-text-base">{{ $assignedClientName }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Priority</dt>
                            <dd class="font-semibold text-text-base">{{ ucfirst($task->priority ?? 'medium') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Schedule assignee</dt>
                            <dd class="font-semibold text-text-base">{{ $scheduleAssignee }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-text-subtle">Estimated minutes</dt>
                            <dd class="font-semibold text-text-base">
                                {{ $task->estimated_minutes ? $task->estimated_minutes . ' min' : '—' }}
                            </dd>
                        </div>
                    </dl>
                    @if ($task->description)
                        <div class="mt-4 text-sm text-text-base whitespace-pre-wrap">{{ $task->description }}</div>
                    @endif
                </div>

                @if ($task->comments?->count())
                    <div class="oh-card p-5">
                        <h2 class="text-sm font-semibold text-text-base">Comments</h2>
                        <div class="mt-3 space-y-3">
                            @foreach ($task->comments as $comment)
                                <div class="rounded-lg p-3 border border-border-default/70 bg-surface-card">
                                    <div class="flex items-center justify-between text-xs text-text-subtle mb-1">
                                        <span>{{ $comment->user?->name ?? 'User' }}</span>
                                        <span>{{ optional($comment->created_at)->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-sm text-text-base whitespace-pre-wrap">{{ $comment->comment }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="oh-card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-text-base">Time history</h2>
                        <span class="text-xs text-text-subtle">{{ count($timeEntries ?? []) }} sessions</span>
                    </div>

                    @if (!empty($timeEntries) && $timeEntries->count())
                        <div class="hidden md:block mt-3 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="text-text-subtle">
                                    <tr class="border-b border-border-default/70">
                                        <th class="py-2 text-left font-medium">User</th>
                                        <th class="py-2 text-left font-medium">Start</th>
                                        <th class="py-2 text-left font-medium">End</th>
                                        <th class="py-2 text-right font-medium">Duration</th>
                                        <th class="py-2 text-left font-medium">Billable</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y" style="--tw-divide-color: rgb(var(--border)/.35);">
                                    @foreach ($timeEntries as $entry)
                                        @php
                                            $start = $entry->start_time ? $entry->start_time->copy()->timezone($tenantTz) : null;
                                            $end = $entry->end_time ? $entry->end_time->copy()->timezone($tenantTz) : null;
                                            $duration = $entry->hours ?? ($start && $end ? round($start->diffInSeconds($end) / 3600, 2) : 0);
                                            $billableClass = $entry->billable ? 'oh-pill--brand' : 'oh-pill--muted';
                                        @endphp
                                        <tr>
                                            <td class="py-2 text-text-base">
                                                {{ $entry->user?->name ?? trim(($entry->user?->first_name ?? '') . ' ' . ($entry->user?->last_name ?? '')) ?? '—' }}
                                            </td>
                                            <td class="py-2 text-text-subtle">
                                                {{ $start?->format('M j, Y g:i A') ?? '—' }}
                                            </td>
                                            <td class="py-2 text-text-subtle">
                                                {{ $end?->format('M j, Y g:i A') ?? 'Running' }}
                                            </td>
                                            <td class="py-2 text-right text-text-base tabular-nums">
                                                {{ number_format((float) $duration, 2) }}h
                                            </td>
                                            <td class="py-2">
                                                <span class="oh-pill {{ $billableClass }}">
                                                    {{ $entry->billable ? 'Billable' : 'Non-billable' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 space-y-2 md:hidden">
                            @foreach ($timeEntries as $entry)
                                @php
                                    $start = $entry->start_time ? $entry->start_time->copy()->timezone($tenantTz) : null;
                                    $end = $entry->end_time ? $entry->end_time->copy()->timezone($tenantTz) : null;
                                    $duration = $entry->hours ?? ($start && $end ? round($start->diffInSeconds($end) / 3600, 2) : 0);
                                    $billableClass = $entry->billable ? 'oh-pill--brand' : 'oh-pill--muted';
                                    $userLabel = $entry->user?->name
                                        ?? trim(($entry->user?->first_name ?? '') . ' ' . ($entry->user?->last_name ?? ''))
                                        ?? '—';
                                @endphp
                                <div class="rounded-lg border border-border-default/70 bg-surface-card p-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-semibold text-text-base">{{ $userLabel }}</span>
                                        <span class="text-text-subtle tabular-nums">{{ number_format((float) $duration, 2) }}h</span>
                                    </div>
                                    <div class="mt-1 text-xs text-text-subtle">
                                        <div>Start: {{ $start?->format('M j, Y g:i A') ?? '—' }}</div>
                                        <div>End: {{ $end?->format('M j, Y g:i A') ?? 'Running' }}</div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="oh-pill {{ $billableClass }}">
                                            {{ $entry->billable ? 'Billable' : 'Non-billable' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-text-subtle mt-3">No time logged yet.</p>
                    @endif
                </div>
            </div>

            <div class="md:col-span-5 space-y-4">
                <div class="oh-card p-5">
                    <h3 class="text-sm font-semibold text-text-base">Status</h3>
                    <p class="text-sm text-text-subtle mt-1">{{ ucfirst($task->status ?? 'todo') }}</p>
                </div>

                @if ($contactId && $isMagicLinkTask)
                    <div class="oh-card p-5">
                        <h3 class="text-sm font-semibold text-text-base">Portal Access</h3>
                        <p class="text-sm text-text-subtle mt-1">
                            Send a fresh magic link to {{ $contactName }}.
                        </p>
                        <form method="POST"
                            action="{{ route('tenant.contacts.magic-link', ['tenant' => $tenant, 'contact' => $contactId]) }}"
                            class="mt-3">
                            @csrf
                            <button type="submit" class="oh-btn oh-btn--primary inline-flex items-center gap-2">
                                <i class="fa-solid fa-link text-[11px]"></i>
                                Send Magic Link
                            </button>
                        </form>
                    </div>
                @endif

                @if (!empty($conversation))
                    <div class="oh-card p-5 flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-text-base">Project Messages</h3>
                                <p class="text-xs text-text-subtle">This task's project conversation.</p>
                            </div>
                            @if (!empty($conversation->public_token))
                                <a href="{{ route('conversation.public', $conversation->public_token) }}" target="_blank"
                                    class="oh-btn oh-btn--ghost">Public Link</a>
                            @endif
                        </div>

                        <div class="border border-border-default/70 rounded-xl bg-surface-card p-3 max-h-64 overflow-y-auto space-y-3">
                        @forelse ($messages as $m)
                            @php
                                $isClient = ($m->sender_type ?? '') === 'client';
                                $bubbleBg = $isClient ? 'rgba(var(--brand-primary)/.10)' : 'rgba(var(--border)/.12)';
                                $bubbleBorder = 'rgba(var(--border)/.6)';
                                $label = $isClient ? 'Client' : 'Team';
                                @endphp
                                <div class="flex {{ $isClient ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[90%] rounded-2xl px-3 py-2 border"
                                        style="background: {{ $bubbleBg }}; border-color: {{ $bubbleBorder }};">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs font-semibold text-text-base">{{ $label }}</span>
                                            <span class="text-xs text-text-subtle">
                                                {{ optional($m->created_at)->format('M d · g:i A') }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-sm text-text-base whitespace-pre-wrap">
                                            {{ $m->body ?? '' }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-2">
                                            <button type="button"
                                                onclick="document.getElementById('edit-msg-{{ $m->id }}').classList.toggle('hidden')"
                                                class="oh-btn oh-btn--ghost h-7 px-2 text-xs">Edit</button>
                                            <form method="POST"
                                                action="{{ route('tenant.projects.messages.destroy', ['tenant' => $tenant, 'project' => $conversation->project_id, 'message' => $m->id]) }}"
                                                class="inline"
                                                onsubmit="return confirm('Delete this message?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="oh-btn oh-btn--ghost h-7 px-2 text-xs text-rose-600">Delete</button>
                                            </form>
                                        </div>
                                        <form id="edit-msg-{{ $m->id }}" class="hidden mt-2 space-y-2" method="POST"
                                            action="{{ route('tenant.projects.messages.update', ['tenant' => $tenant, 'project' => $conversation->project_id, 'message' => $m->id]) }}">
                                            @csrf
                                            @method('PUT')
                                            <textarea name="body" rows="2"
                                                class="w-full rounded-lg border border-border-default/70 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $m->body }}</textarea>
                                            <div class="flex justify-end gap-2 text-xs">
                                                <button type="button"
                                                    onclick="document.getElementById('edit-msg-{{ $m->id }}').classList.add('hidden')"
                                                    class="oh-btn oh-btn--ghost h-7 px-3">Cancel</button>
                                                <button type="submit" class="oh-btn oh-btn--primary h-7 px-3">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl p-3 border border-border-default/70 bg-surface-card">
                                    <p class="text-sm text-text-subtle">No messages yet. Send a note to start the thread.</p>
                                </div>
                            @endforelse
                        </div>

                        @if (!empty($conversation->project_id))
                            <form method="POST"
                                action="{{ route('tenant.projects.messages.store', ['tenant' => $tenant, 'project' => $conversation->project_id]) }}">
                                @csrf
                                <div class="rounded-xl p-3 border border-border-default/70 bg-surface-muted/60 space-y-2">
                                    <textarea name="body" rows="3" placeholder="Write an update…"
                                        class="w-full bg-transparent text-sm text-text-base placeholder:text-[rgb(var(--text-subtle))]
                           focus:outline-none resize-none" required></textarea>
                                    <div class="flex items-center justify-between text-xs text-text-subtle">
                                        <span>Visible to client</span>
                                        <button type="submit" class="oh-btn oh-btn--primary">
                                            <i class="fa-regular fa-paper-plane text-xs"></i>
                                            Send
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
