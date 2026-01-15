@extends('layouts.portal')

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-6 space-y-4">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-text-base">Project Messages</h1>
                <p class="text-sm text-text-subtle">{{ $project->project_name }}</p>
            </div>
            <a href="{{ route('portal.messages.index') }}" class="oh-btn">Back</a>
        </div>

        <div class="rounded-2xl ring-1 p-4 space-y-3"
            style="background: rgb(var(--surface)); --tw-ring-color: rgb(var(--border)/.6);">
            @forelse($messages as $m)
                <div class="p-3 rounded-xl" style="background: rgb(var(--surface-muted));">
                    <div class="text-xs mb-1" style="color: rgb(var(--text-subtle));">
                        {{ $m->sender_type === 'client' ? 'You' : 'Team' }} · {{ $m->created_at->format('M j, g:i a') }}
                    </div>
                    <div class="text-sm" style="color: rgb(var(--text)); white-space: pre-wrap;">{{ $m->body }}</div>
                </div>
            @empty
                <p class="text-sm" style="color: rgb(var(--text-subtle));">No messages yet. Start the conversation.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('portal.projects.messages.store', $project) }}"
            class="rounded-2xl ring-1 p-4 space-y-3"
            style="background: rgb(var(--surface)); --tw-ring-color: rgb(var(--border)/.6);">
            @csrf
            <textarea name="body" rows="4"
                class="w-full rounded-xl p-3 text-sm border border-border-default bg-surface-card text-text-base focus:outline-none focus:ring-1"
                style="border-color: rgb(var(--border));" placeholder="Write a message...">{{ old('body') }}</textarea>

            <div class="flex justify-end">
                <button class="oh-btn oh-btn--primary">Send</button>
            </div>
        </form>

    </div>
@endsection
