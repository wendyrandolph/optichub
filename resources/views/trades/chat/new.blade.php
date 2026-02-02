@extends('layouts.trades')

@section('title', 'Direct Message')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Team</p>
                <h1 class="text-2xl font-semibold text-text-base">Direct Message</h1>
                <p class="text-sm text-text-subtle mt-1">Start a private chat with a teammate.</p>
            </div>
            <a class="oh-btn" href="{{ route('tenant.trades.chat.index', ['tenant' => $tenantKey]) }}">Back to chat</a>
        </div>

        <div class="oh-card p-5">
            <div class="text-sm font-semibold text-text-base">Start a direct message</div>
            <p class="text-sm text-text-subtle mt-1">Pick a teammate to open a private chat.</p>

            <form method="GET" action="#" class="mt-4 space-y-3" id="dm-start-form"
                data-dm-base-url="{{ route('tenant.trades.chat.dm.start', ['tenant' => $tenantKey, 'user' => 'USER_ID']) }}">
                <div>
                    <label class="text-xs font-semibold text-text-subtle" for="user_id">Teammate</label>
                    <select id="user_id" name="user_id" class="oh-input w-full mt-1">
                        <option value="">Select a teammate</option>
                        @foreach ($dmTargets as $target)
                            <option value="{{ $target['id'] }}" @selected(old('user_id') == $target['id'])
                                @disabled($target['disabled'])>
                                {{ $target['label'] }}{{ $target['note'] ? ' (' . $target['note'] . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                    @if ($dmTargets->isEmpty())
                        <div class="text-xs text-text-subtle mt-1">No team members available yet.</div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="oh-btn oh-btn--primary">Start direct message</button>
                    <a class="oh-btn" href="{{ route('tenant.trades.chat.index', ['tenant' => $tenantKey]) }}">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('dm-start-form');
            const select = document.getElementById('user_id');
            if (!form || !select) return;

            const baseUrl = form.dataset.dmBaseUrl || '';
            const resolveUrl = (userId) => baseUrl.replace('USER_ID', userId);

            form.addEventListener('submit', (event) => {
                if (!select.value) {
                    event.preventDefault();
                    return;
                }
                event.preventDefault();
                window.location = resolveUrl(select.value);
            });
        });
    </script>
@endsection
