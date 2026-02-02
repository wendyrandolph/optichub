@extends('layouts.app')

@section('title', 'Edit Automation')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div>
            <a href="{{ route('tenant.settings.automations.index', ['tenant' => $tenant->id]) }}" class="oh-btn oh-btn--ghost w-fit">
                Back to automations
            </a>
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-text-base">Edit automation</h1>
            <p class="text-sm text-text-subtle mt-1">Update the trigger or actions for this automation.</p>
        </div>

        <form method="POST" action="{{ route('tenant.settings.automations.update', ['tenant' => $tenant->id, 'rule' => $rule->id]) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('settings.automations-form', ['rule' => $rule, 'templates' => $templates])
            <div class="flex items-center justify-end gap-2">
                <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
            </div>
        </form>
    </div>
@endsection
