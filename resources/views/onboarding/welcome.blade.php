@extends('layouts.app')

@section('title', 'Welcome to Renlo')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('onboarding._steps', ['current' => 1])

        <div class="rounded-xl bg-surface-card/90 border border-border-default p-6 sm:p-7 shadow-sm">
            <h1 class="text-2xl font-semibold text-text-base tracking-tight mb-2">
                Welcome — let’s set up your workspace 👋
            </h1>
            <p class="text-sm text-text-subtle mb-4">
                We’ll take 2–3 minutes to set up the essentials so Renlo feels like your workspace—not a generic tool.
                You can skip any step and come back later.
            </p>

            <ul class="list-disc list-inside text-sm text-text-subtle mb-4 space-y-1.5">
                <li>Make Renlo look like your business</li>
                <li>Add a real client you’re already working with</li>
                <li>Create a project you can actually use</li>
                <li>Invite a teammate (or skip for now)</li>
            </ul>

            <p class="text-xs text-text-subtle mb-5">
                Nothing here is permanent — you can change or skip anything.
            </p>

            <form method="POST" action="{{ route('onboarding.workspace.store') }}" class="space-y-4">
                @csrf
                <div class="space-y-2">
                    <p class="text-sm font-medium text-text-base">Workspace type</p>
                    <div class="grid gap-2">
                        <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                            <input type="radio" name="workspace_type" value="creative"
                                @checked(old('workspace_type', $tenant?->workspace_type ?? 'creative') === 'creative')>
                            <span class="text-text-base">Creative</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                            <input type="radio" name="workspace_type" value="trades"
                                @checked(old('workspace_type', $tenant?->workspace_type) === 'trades')>
                            <span class="text-text-base">Trades / Services</span>
                        </label>
                    </div>
                    <p class="text-xs text-text-subtle">You can change this later in settings.</p>
                </div>

                <div class="flex items-center justify-between gap-3 flex-wrap pt-1 pb-1">
                    <button type="submit"
                        class="btn btn--primary bg-[rgb(var(--ui-primary))] hover:bg-[rgb(var(--ui-primary-hover))] text-white border border-transparent">
                        Set up my workspace
                    </button>
                    <a href="{{ route('admin.dashboard') }}"
                        class="text-sm text-text-subtle hover:text-text-base whitespace-nowrap">
                        Skip for now
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
