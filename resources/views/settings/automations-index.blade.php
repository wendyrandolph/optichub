@extends('layouts.app')

@section('title', 'Automations')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-text-base">Automations</h1>
                <p class="text-sm text-text-subtle mt-1">Automate next steps when proposals are sent or approved.</p>
            </div>
            <a href="{{ route('tenant.settings.automations.create', ['tenant' => $tenant->id]) }}"
                class="oh-btn oh-btn--primary">New automation</a>
        </div>

        <div class="space-y-4">
            @forelse ($rules as $rule)
                <div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-semibold text-text-base">{{ $rule->name }}</h2>
                                <span class="oh-pill {{ $rule->enabled ? 'oh-pill--success' : 'oh-pill--muted' }}">
                                    {{ $rule->enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                            <p class="text-sm text-text-subtle mt-1">
                                Trigger: {{ str_replace('_', ' ', $rule->trigger_key ?? $rule->trigger ?? '—') }}
                            </p>
                            <p class="text-xs text-text-subtle mt-2">{{ $rule->actionItems->count() }} action(s)</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('tenant.settings.automations.edit', ['tenant' => $tenant->id, 'rule' => $rule->id]) }}"
                                class="oh-btn">Edit</a>
                            <form method="POST"
                                action="{{ route('tenant.settings.automations.destroy', ['tenant' => $tenant->id, 'rule' => $rule->id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="oh-btn oh-btn--ghost">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6 text-center">
                    <p class="text-sm text-text-subtle">No automations yet.</p>
                    <a href="{{ route('tenant.settings.automations.create', ['tenant' => $tenant->id]) }}"
                        class="oh-btn oh-btn--primary mt-4">Create your first automation</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection
