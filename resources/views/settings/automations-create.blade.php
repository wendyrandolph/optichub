@extends('layouts.app')

@section('title', 'Create Automation')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <a href="{{ route('tenant.settings.automations.index', ['tenant' => $tenant->id]) }}" class="oh-btn oh-btn--ghost w-fit">
                Back to automations
            </a>
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-text-base">New automation</h1>
            <p class="text-sm text-text-subtle mt-1">Define what happens when a proposal is sent or approved.</p>
        </div>

        <form method="POST" action="{{ route('tenant.settings.automations.store', ['tenant' => $tenant->id]) }}" class="space-y-6">
            @csrf
            @include('settings.automations-form', ['templates' => $templates])
            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="oh-btn oh-btn--primary">Save automation</button>
            </div>
        </form>
    </div>
@endsection
