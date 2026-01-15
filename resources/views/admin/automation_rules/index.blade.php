@extends('layouts.app')

@section('title', 'Automation Rules')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Admin</p>
                <h1 class="text-2xl font-semibold text-text-base">Automation Rules</h1>
                <p class="text-sm text-text-subtle mt-1">Enable/disable rules and run test jobs.</p>
            </div>
        </header>

        <div class="space-y-3">
            @foreach ($rules as $rule)
                @php
                    $lastRun = $rule->runs->first();
                @endphp
                <div class="oh-card p-4 flex flex-col gap-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="font-semibold text-text-base">{{ $rule->name }}</div>
                            <div class="text-xs text-text-subtle">Trigger: {{ $rule->trigger }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.automation-rules.update', $rule) }}" class="flex items-center gap-2">
                            @csrf @method('PUT')
                            <label class="flex items-center gap-2 text-sm text-text-subtle">
                                <input type="checkbox" name="active" value="1" @checked($rule->active)>
                                Active
                            </label>
                            <button type="submit" class="oh-btn oh-btn--primary text-xs">Save</button>
                        </form>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-text-subtle">
                        <span>Last run:
                            @if ($lastRun)
                                {{ $lastRun->created_at?->diffForHumans() }} ({{ $lastRun->status }})
                            @else
                                never
                            @endif
                        </span>
                        @if (!empty($lastRun?->error))
                            <span class="text-rose-600">Error: {{ $lastRun->error }}</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.automation-rules.test', $rule) }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center">
                        @csrf
                        <label class="grid gap-1 text-sm md:col-span-2">
                            <span class="text-text-subtle">Test on opportunity ID</span>
                            <input type="number" name="opportunity_id" class="h-9 rounded-lg bg-surface-card text-text-base px-3 text-sm border border-border-default focus:ring-1 focus:ring-brand-primary" required>
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="dry_run" value="1" checked>
                            Dry run
                        </label>
                        <div class="md:col-span-3 flex justify-end">
                            <button type="submit" class="oh-btn">Test rule</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection
