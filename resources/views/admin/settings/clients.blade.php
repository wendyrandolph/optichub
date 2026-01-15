@extends('layouts.app')

@section('title', 'Client settings')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wide text-text-subtle">Settings / Clients</p>
            <h1 class="text-2xl font-semibold text-text-base">Client preferences</h1>
            <p class="text-sm text-text-subtle">Set the default client type and whether to ask each time when creating clients.</p>
        </div>

        <div class="rounded-xl bg-surface-card/80 border border-border-default/60 p-6 shadow-sm">
            <form method="POST" action="{{ route('tenant.settings.clients.update', ['tenant' => $tenant->id]) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid gap-2">
                    <span class="text-sm text-text-subtle font-medium">Default client type</span>
                    @php
                        $defaultType = old('default_client_type', $tenant->default_client_type ?? 'person');
                    @endphp
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="default_client_type" value="person" class="accent-[rgb(var(--ui-primary))]" @checked($defaultType === 'person')>
                            <span class="text-sm">Person</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="default_client_type" value="company" class="accent-[rgb(var(--ui-primary))]" @checked($defaultType === 'company')>
                            <span class="text-sm">Company</span>
                        </label>
                    </div>
                </div>

                <div class="grid gap-2">
                    <span class="text-sm text-text-subtle font-medium">When creating a new client</span>
                    @php
                        $prompt = old('client_type_prompt', $tenant->client_type_prompt ?? 'use_default');
                    @endphp
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="client_type_prompt" value="use_default" class="accent-[rgb(var(--ui-primary))]" @checked($prompt === 'use_default')>
                            <span class="text-sm">Use my default</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-text-base">
                            <input type="radio" name="client_type_prompt" value="ask_each_time" class="accent-[rgb(var(--ui-primary))]" @checked($prompt === 'ask_each_time')>
                            <span class="text-sm">Ask each time</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-border-default/60 mt-4">
                    <a href="{{ route('tenant.settings.profile', ['tenant' => $tenant->id]) }}" class="text-sm text-text-subtle hover:text-text-base">Cancel</a>
                    <button type="submit"
                        class="btn btn--primary text-sm bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Save preferences
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
