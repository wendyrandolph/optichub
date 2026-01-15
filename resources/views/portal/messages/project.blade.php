@extends('layouts.portal')

@section('title', 'Project Messages')

@section('content')
    <div class="oh-page space-y-6">
        <div class="mb-6 space-y-2">
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('portal.dashboard') }}" class="text-[rgb(var(--brand-primary))] hover:text-[rgb(var(--brand-secondary))]">
                    <i class="fa-solid fa-arrow-left"></i> Dashboard
                </a>
                <span class="text-text-subtle">|</span>
                <a href="{{ route('portal.projects.show', $project->id) }}"
                    class="text-[rgb(var(--brand-primary))] hover:text-[rgb(var(--brand-secondary))]">
                    Project <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <h1 class="text-2xl font-semibold text-text-base mt-2">
                Messages for {{ $project->project_name }}
            </h1>
            <p class="text-text-subtle text-sm mt-1">
                Chat with your provider about this project. Everyone linked to your company can see these messages.
            </p>
        </div>

        {{-- Project context --}}
        <div class="bg-surface-card rounded-xl border border-border-default shadow-sm p-4 sm:p-5 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-text-subtle">Project</p>
                    <p class="text-base font-semibold text-text-base">{{ $project->project_name }}</p>
                    <p class="text-xs text-text-subtle">Status: {{ ucfirst($project->status ?? 'open') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('portal.projects.show', $project->id) }}"
                        class="inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium text-[rgb(var(--brand-primary))] hover:text-[rgb(var(--brand-secondary))] bg-[rgba(var(--brand-primary),0.08)]">
                        View project
                    </a>
                </div>
            </div>
        </div>

        @php
            $hexToRgb = function (?string $hex, string $fallback) {
                $h = ltrim($hex ?: $fallback, '#');
                if (strlen($h) === 3) {
                    $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
                }
                $int = hexdec($h);
                $r = ($int >> 16) & 255;
                $g = ($int >> 8) & 255;
                $b = $int & 255;
                return "{$r} {$g} {$b}";
            };
            $clientMsgHex = $client?->portal_client_message_color ?? '#1C2E70';
            $teamMsgHex = $client?->portal_team_message_color ?? '#E6EAF2';
            $clientMsgRgb = $hexToRgb($clientMsgHex, '#1C2E70');
            $teamMsgRgb = $hexToRgb($teamMsgHex, '#E6EAF2');
        @endphp
        <div class="bg-surface-card rounded-xl border border-border-default shadow-sm"
            style="--client-msg: {{ $clientMsgRgb }}; --team-msg: {{ $teamMsgRgb }};">
            <div class="p-4 sm:p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                @forelse($messages as $message)
                    @php
                        $isProvider = !empty($message->user_id);
                        $authorName = $isProvider
                            ? $message->user->name ?? 'Provider'
                            : trim(
                                ($message->client->first_name ?? '') . ' ' . ($message->client->last_name ?? 'Client'),
                            );
                    @endphp
                    <div
                        class="p-3 rounded-lg border {{ $isProvider ? 'bg-[rgba(var(--team-msg),0.12)] border-[rgba(var(--team-msg),0.35)]' : 'bg-[rgba(var(--client-msg),0.08)] border-[rgba(var(--client-msg),0.28)]' }}">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <span class="text-sm font-semibold text-text-base">{{ $authorName }}</span>
                            <span class="text-xs text-text-subtle">{{ $message->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-text-base whitespace-pre-line">{{ $message->body }}</p>
                    </div>
                @empty
                    <p class="text-text-subtle text-sm">No messages yet.</p>
                @endforelse
            </div>

            <div class="border-t border-border-default p-4 sm:p-6">
                <form method="POST" action="{{ route('portal.projects.messages.store', $project->id) }}"
                    class="space-y-3">
                    @csrf
                    <label class="block text-sm font-medium text-text-base" for="body">Add a message</label>
                    <textarea id="body" name="body" rows="3" required
                        class="w-full rounded-lg border border-border-default focus:border-[rgb(var(--brand-primary))] focus:ring-[rgba(var(--brand-primary),0.35)] text-sm p-3">{{ old('body') }}</textarea>
                    <div class="flex justify-end">
                        <button type="submit"
                            class="oh-btn oh-btn--primary">
                            Send
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
