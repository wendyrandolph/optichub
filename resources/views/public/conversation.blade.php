<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Conversation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 text-gray-900">
    <div class="max-w-5xl mx-auto px-4 py-10 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-500">Project</p>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $project->project_name ?? 'Project' }}</h1>
                <p class="text-sm text-gray-600 mt-1">Shared review link</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 sm:p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-900">Conversation</h2>
                            <span class="text-xs text-gray-500">Reply below</span>
                        </div>
                        @forelse($messages as $m)
                            <div class="p-3 rounded-lg border border-gray-100 bg-gray-50">
                                <div class="text-xs text-gray-500 mb-1">
                                    {{ ucfirst($m->sender_type ?? 'client') }} • {{ $m->created_at?->format('M j, g:i a') }}
                                </div>
                                <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $m->body }}</div>
                                @if(($m->sender_type ?? '') === 'client')
                                    <div class="flex items-center gap-2 mt-2">
                                        <button type="button"
                                            onclick="document.getElementById('edit-msg-{{ $m->id }}').classList.toggle('hidden')"
                                            class="text-xs text-indigo-600 hover:underline">Edit</button>
                                        <form method="POST"
                                            action="{{ route('conversation.public.message_delete', [$conversation->public_token, $m->id]) }}"
                                            onsubmit="return confirm('Delete this message?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs text-red-600 hover:underline" type="submit">Delete</button>
                                        </form>
                                    </div>
                                    <form id="edit-msg-{{ $m->id }}" class="hidden mt-2 space-y-2"
                                        method="POST"
                                        action="{{ route('conversation.public.message_update', [$conversation->public_token, $m->id]) }}">
                                        @csrf
                                        <textarea name="body" rows="2"
                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ $m->body }}</textarea>
                                        <div class="flex justify-end gap-2 text-xs">
                                            <button type="button"
                                                onclick="document.getElementById('edit-msg-{{ $m->id }}').classList.add('hidden')"
                                                class="px-3 py-1 rounded bg-gray-100 text-gray-600">Cancel</button>
                                            <button type="submit"
                                                class="px-3 py-1 rounded bg-indigo-600 text-white">Save</button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No messages yet.</p>
                        @endforelse
                        <form method="POST" action="{{ route('conversation.public.message', $conversation->public_token) }}"
                            class="space-y-2">
                            @csrf
                            <textarea name="body" rows="3" required
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Write a reply..."></textarea>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500">
                                    Send
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="p-4 sm:p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-gray-900">Tasks for this project</h2>
                        </div>
                        @forelse($tasks as $task)
                            <div class="p-3 rounded-lg border border-gray-100 bg-gray-50">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $task->title }}</p>
                                        <p class="text-xs text-gray-500">
                                            Status: {{ ucfirst($task->status ?? 'open') }}
                                            @if ($task->due_date)
                                                · Due {{ $task->due_date->format('M d, Y') }}
                                            @endif
                                        </p>
                                    </div>
                                    @if ($task->priority)
                                        <span class="text-xs text-gray-500">{{ ucfirst($task->priority) }}</span>
                                    @endif
                                </div>
                                @if ($task->description)
                                    <p class="text-sm text-gray-700 mt-2">{{ $task->description }}</p>
                                @endif
                                <form method="POST"
                                    action="{{ route('conversation.public.task_status', [$conversation->public_token, $task->id]) }}"
                                    class="mt-2 flex items-center gap-2">
                                    @csrf
                                    <select name="status"
                                        class="h-9 rounded-lg border border-gray-200 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        @php $st = $task->status ?? 'open'; @endphp
                                        <option value="open" {{ $st === 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ $st === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="completed" {{ $st === 'completed' ? 'selected' : '' }}>Completed</option>
                                    </select>
                                    <button type="submit"
                                        class="inline-flex items-center px-3 h-9 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">
                                        Update
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No tasks assigned yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-900">Project Info</h3>
                    <dl class="mt-3 space-y-1 text-sm text-gray-700">
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Contact</dt>
                            <dd>{{ $project->contact?->firstName ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Company</dt>
                            <dd>{{ $project->contact?->company_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-500">Status</dt>
                            <dd>{{ ucfirst($project->status ?? 'open') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-4 sm:p-6">
                    <h3 class="text-sm font-semibold text-gray-900">Files</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($uploads as $file)
                            <a href="{{ route('conversation.public.file', [$conversation->public_token, $file->id]) }}"
                                class="block text-sm text-indigo-600 hover:text-indigo-800"
                                target="_blank">
                                {{ $file->original_name ?? basename($file->path) }}
                            </a>
                        @empty
                            <p class="text-sm text-gray-500">No files shared yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <p class="text-xs text-gray-500">If you believe this link was sent to you by mistake, you can ignore this
            message.</p>
    </div>
</body>

</html>
