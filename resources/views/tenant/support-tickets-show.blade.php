@php
    $layout = ($tenant?->workspace_type ?? 'creative') === 'trades' ? 'layouts.trades' : 'layouts.app';
@endphp

@extends($layout)

@section('title', $ticket->subject)

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <a href="{{ route('tenant.support.tickets.index', ['tenant' => $tenant->id]) }}" class="text-xs text-text-subtle hover:text-text-base">Back to tickets</a>

        <div class="oh-card p-5 space-y-2">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="oh-pill">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                <span class="oh-pill oh-pill--muted">{{ ucfirst($ticket->category) }}</span>
            </div>
            <h1 class="text-2xl font-semibold text-text-base">{{ $ticket->subject }}</h1>
            <p class="text-sm text-text-subtle">Submitted {{ $ticket->created_at->format('M j, Y g:ia') }}</p>
            <p class="text-sm text-text-base">{{ $ticket->body }}</p>
        </div>

        <div class="oh-card p-5 space-y-4">
            <h2 class="text-sm font-semibold text-text-base">Conversation</h2>
            <div class="space-y-3">
                @forelse ($ticket->messages as $message)
                    <div class="border border-border-default rounded-lg p-3">
                        <div class="flex items-center justify-between text-xs text-text-subtle">
                            <span>{{ $message->sender_type === 'provider' ? 'Renlo team' : 'You' }}</span>
                            <span>{{ $message->created_at->format('M j, Y g:ia') }}</span>
                        </div>
                        <p class="text-sm mt-2 text-text-base">{{ $message->body }}</p>
                    </div>
                @empty
                    <p class="text-sm text-text-subtle">No replies yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('tenant.support.tickets.reply', ['tenant' => $tenant->id, 'ticket' => $ticket->id]) }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Reply</span>
                    <textarea name="body" rows="3" class="oh-textarea" required></textarea>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Attachments (optional)</span>
                    <input type="file" name="attachments[]" multiple class="oh-input h-10">
                </label>
                <div class="flex items-center justify-end gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary">Send reply</button>
                </div>
            </form>
        </div>
    </div>
@endsection
