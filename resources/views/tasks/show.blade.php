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
        @endphp
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs text-text-subtle">Task</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $task->title }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    Status: {{ ucfirst($task->status ?? 'todo') }}
                    @if ($task->due_date)
                        · Due {{ optional($task->due_date)->format('M d, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('tenant.tasks.edit', ['tenant' => $tenant, 'task' => $task->id]) }}" class="oh-btn"
                    title="Edit task" aria-label="Edit task">
                    <i class="fa-solid fa-pen text-[10px]"></i> Edit
                </a>
                @if ($canDelete)
                    <form method="POST"
                        action="{{ route('tenant.tasks.destroy', ['tenant' => $tenant, 'task' => $task->id]) }}"
                        onsubmit="return confirm('Delete this task? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="oh-btn" title="Delete task" aria-label="Delete task">
                            <i class="fa-solid fa-trash text-[10px]"></i>
                        </button>
                    </form>
                @endif
                <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant]) }}" class="oh-btn">All Tasks</a>
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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-4">
                <div class="oh-card p-5">
                    <h2 class="text-sm font-semibold text-text-base">Details</h2>
                    <dl class="mt-3 space-y-2 text-sm text-text-subtle">
                        <div class="flex justify-between">
                            <dt>Project</dt>
                            <dd>{{ $task->project?->project_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Assigned User</dt>
                            <dd>{{ $task->user?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Assigned Client</dt>
                            <dd>{{ $task->client?->firstName ?? $task->client?->first_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Priority</dt>
                            <dd>{{ ucfirst($task->priority ?? 'medium') }}</dd>
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
                                <div class="rounded-lg p-3 ring-1 ring-[rgb(var(--border)/.6)] bg-[rgb(var(--surface))]">
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
            </div>

            <div class="space-y-4">
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
                                    class="oh-btn">Public Link</a>
                            @endif
                        </div>

                        <div class="border border-[rgb(var(--border)/.6)] rounded-xl p-3 max-h-64 overflow-y-auto space-y-3">
                        @forelse ($messages as $m)
                            @php
                                $isClient = ($m->sender_type ?? '') === 'client';
                                $bubbleBg = $isClient ? 'rgba(var(--brand-primary)/.10)' : 'rgba(var(--border)/.20)';
                                $bubbleRing = 'rgb(var(--border)/.6)';
                                    $label = $isClient ? 'Client' : 'Team';
                                @endphp
                                <div class="flex {{ $isClient ? 'justify-start' : 'justify-end' }}">
                                    <div class="max-w-[90%] rounded-2xl px-3 py-2 ring-1"
                                        style="background: {{ $bubbleBg }}; --tw-ring-color: {{ $bubbleRing }};">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-[11px] font-semibold"
                                                style="color: rgb(var(--text));">{{ $label }}</span>
                                            <span class="text-[11px]" style="color: rgb(var(--text-subtle));">
                                                {{ optional($m->created_at)->format('M d · g:i A') }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-sm" style="color: rgb(var(--text)); white-space: pre-wrap;">
                                            {{ $m->body ?? '' }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-2">
                                            <button type="button"
                                                onclick="document.getElementById('edit-msg-{{ $m->id }}').classList.toggle('hidden')"
                                                class="text-[11px] text-indigo-600 hover:underline">Edit</button>
                                            <form method="POST"
                                                action="{{ route('tenant.projects.messages.destroy', ['tenant' => $tenant, 'project' => $conversation->project_id, 'message' => $m->id]) }}"
                                                class="inline"
                                                onsubmit="return confirm('Delete this message?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-[11px] text-red-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                        <form id="edit-msg-{{ $m->id }}" class="hidden mt-2 space-y-2" method="POST"
                                            action="{{ route('tenant.projects.messages.update', ['tenant' => $tenant, 'project' => $conversation->project_id, 'message' => $m->id]) }}">
                                            @csrf
                                            @method('PUT')
                                            <textarea name="body" rows="2"
                                                class="w-full rounded-lg border border-[rgb(var(--border)/.6)] px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $m->body }}</textarea>
                                            <div class="flex justify-end gap-2 text-[11px]">
                                                <button type="button"
                                                    onclick="document.getElementById('edit-msg-{{ $m->id }}').classList.add('hidden')"
                                                    class="px-3 py-1 rounded bg-[rgb(var(--surface))] text-text-subtle">Cancel</button>
                                                <button type="submit"
                                                    class="px-3 py-1 rounded bg-indigo-600 text-white font-semibold">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-xl p-3 ring-1 ring-[rgb(var(--border)/.6)] bg-[rgb(var(--surface))]">
                                    <p class="text-sm text-text-subtle">No messages yet. Send a note to start the thread.</p>
                                </div>
                            @endforelse
                        </div>

                        @if (!empty($conversation->project_id))
                            <form method="POST"
                                action="{{ route('tenant.projects.messages.store', ['tenant' => $tenant, 'project' => $conversation->project_id]) }}">
                                @csrf
                                <div class="rounded-xl p-3 ring-1 ring-[rgb(var(--border)/.6)] bg-[rgb(var(--surface))] space-y-2">
                                    <textarea name="body" rows="3" placeholder="Write an update…"
                                        class="w-full bg-transparent text-sm text-text-base placeholder:text-[rgb(var(--text-subtle))]
                           focus:outline-none resize-none" required></textarea>
                                    <div class="flex items-center justify-between text-[11px] text-text-subtle">
                                        <span>Visible to client</span>
                                        <button type="submit" class="oh-btn oh-btn--primary">
                                            <i class="fa-regular fa-paper-plane text-[10px]"></i>
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
