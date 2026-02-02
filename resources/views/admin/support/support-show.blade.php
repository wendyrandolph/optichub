@extends('layouts.app')

@section('title', 'Support Ticket')

@section('content')
    @php
        $statusPills = [
            'received' => 'oh-pill--info',
            'in_progress' => 'oh-pill--primary',
            'waiting_on_customer' => 'oh-pill--warning',
            'resolved' => 'oh-pill--success',
        ];
        $typePills = [
            'bug' => 'oh-pill--danger',
            'question' => 'oh-pill--muted',
            'feature' => 'oh-pill--info',
        ];
        $creatorName = $ticket->creator ? trim(($ticket->creator->first_name ?? '') . ' ' . ($ticket->creator->last_name ?? '')) : 'Provider';
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <p class="text-[11px] uppercase tracking-[0.2em] text-text-subtle">Provider</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $ticket->subject }}</h1>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="oh-pill {{ $statusPills[$ticket->status] ?? 'oh-pill' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    <span class="oh-pill {{ $typePills[$ticket->category] ?? 'oh-pill' }}">{{ ucfirst($ticket->category) }}</span>
                    @if ($ticket->tenant)
                        <span class="oh-pill oh-pill--muted">{{ $ticket->tenant->name }}</span>
                    @endif
                    @if ($ticket->priority)
                        <span class="oh-pill oh-pill--muted">{{ ucfirst($ticket->priority) }} priority</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('admin.support.index') }}" class="oh-btn">Back to inbox</a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <div class="oh-card p-5 space-y-2">
                    <p class="text-sm text-text-subtle">Opened by {{ $creatorName }} on {{ $ticket->created_at->format('M j, Y g:ia') }}</p>
                    <p class="text-sm text-text-base">{{ $ticket->body }}</p>
                </div>

                <div class="oh-card p-5 space-y-4">
                    <h2 class="text-sm font-semibold text-text-base">Conversation</h2>
                    <div class="space-y-3">
                        @forelse ($ticket->messages as $message)
                            <div class="border border-border-default rounded-lg p-3">
                                <div class="flex items-center justify-between text-xs text-text-subtle">
                                    <span>
                                        {{ $message->author ? trim(($message->author->first_name ?? '') . ' ' . ($message->author->last_name ?? '')) : ($message->adminAuthor ? trim(($message->adminAuthor->first_name ?? '') . ' ' . ($message->adminAuthor->last_name ?? '')) : 'Provider') }}
                                    </span>
                                    <span>{{ $message->created_at->format('M j, Y g:ia') }}</span>
                                </div>
                                <p class="text-sm mt-2 {{ $message->is_internal ? 'text-text-subtle' : 'text-text-base' }}">{{ $message->body }}</p>
                                @if ($message->is_internal)
                                    <span class="oh-pill oh-pill--muted text-[11px] mt-2 inline-flex">Internal note</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-text-subtle">No messages yet.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('admin.support.messages.store', $ticket) }}" class="space-y-2">
                        @csrf
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Add a reply</span>
                            <textarea name="body" rows="3" class="oh-textarea" required></textarea>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-subtle">
                            <input type="checkbox" name="is_internal" value="1" class="h-4 w-4">
                            Internal note only
                        </label>
                        <button type="submit" class="oh-btn oh-btn--primary">Post message</button>
                    </form>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="oh-card p-4 space-y-3">
                    <h2 class="text-sm font-semibold text-text-base">Status</h2>
                    <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="space-y-2">
                        @csrf
                        <select name="status" class="oh-select h-10">
                            @foreach (['received' => 'Received', 'in_progress' => 'In progress', 'waiting_on_customer' => 'Waiting on customer', 'resolved' => 'Resolved'] as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="priority" class="oh-select h-10">
                            <option value="">Normal</option>
                            @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="oh-btn">Update status</button>
                    </form>
                </div>

                <div class="oh-card p-4 space-y-2">
                    <h2 class="text-sm font-semibold text-text-base">Context</h2>
                    <div class="text-xs text-text-subtle space-y-1">
                        <div>Route: {{ data_get($ticket->context, 'route_name', '—') }}</div>
                        <div>URL: {{ data_get($ticket->context, 'url', '—') }}</div>
                        <div>Viewport: {{ data_get($ticket->context, 'viewport', '—') }}</div>
                        <div>App version: {{ data_get($ticket->context, 'app_version', '—') }}</div>
                        <div>User agent: {{ data_get($ticket->context, 'user_agent', '—') }}</div>
                        <div>IP: {{ data_get($ticket->context, 'ip', '—') }}</div>
                    </div>
                </div>

                <div class="oh-card p-4 space-y-2">
                    <h2 class="text-sm font-semibold text-text-base">Attachments</h2>
                    @if ($ticket->attachments->isEmpty())
                        <p class="text-sm text-text-subtle">No attachments.</p>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($ticket->attachments as $file)
                                <li class="flex items-center justify-between">
                                    <span class="truncate">{{ $file->original_name ?? basename($file->file_path) }}</span>
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($file->file_path) }}"
                                        class="oh-btn text-xs">Download</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection
