@extends('layouts.app')

@section('title', $channel->name ?? 'Chat')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
        $currentUser = auth('admin')->user() ?? auth()->user();
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wider text-text-subtle">Communication</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $channel->name }}</h1>
            </div>
            <a href="{{ route('tenant.chat.index', ['tenant' => $tenantId]) }}" class="oh-btn">Back</a>
        </div>

        <div class="oh-card border border-border-default/70 shadow-card">
            <div id="chat-messages" class="space-y-3 max-h-[420px] overflow-y-auto">
                @forelse ($messages as $message)
                    @php
                        $author = $message->user;
                        $authorName =
                            trim(($author->first_name ?? '') . ' ' . ($author->last_name ?? '')) ?:
                            $author->username ?? 'User';
                        $isMe = $currentUser && $author && (int) $author->id === (int) $currentUser->id;
                    @endphp
                    <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%] rounded-xl border border-border-default/60 px-3 py-2 bg-surface-card">
                            <div class="text-[11px] text-text-subtle">{{ $authorName }}</div>
                            <div class="text-sm text-text-base whitespace-pre-wrap">{{ $message->body }}</div>
                            <div class="text-[11px] text-text-subtle mt-1">
                                {{ $message->created_at?->format('M j, g:ia') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-text-subtle">No messages yet.</p>
                @endforelse
            </div>

            <form method="POST"
                action="{{ route('tenant.chat.messages.store', ['tenant' => $tenantId, 'channel' => $channel->id]) }}"
                class="mt-4 border-t border-border-default/60 pt-4 space-y-2">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Message</span>
                    <textarea name="body" rows="3" class="oh-textarea" required>{{ old('body') }}</textarea>
                </label>
                <div class="flex justify-end">
                    <button type="submit" class="oh-btn oh-btn--primary">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            const box = document.getElementById('chat-messages');
            if (box) {
                box.scrollTop = box.scrollHeight;
            }
        })();
    </script>
@endsection
