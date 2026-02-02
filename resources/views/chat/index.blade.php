@extends('layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="oh-page space-y-6">
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Communication</p>
            <h1 class="text-2xl font-semibold text-text-base">Messages</h1>
            <p class="text-sm text-text-subtle">Reach your team and project channels.</p>
        </div>

        <div class="oh-card border border-border-default/70 shadow-card p-5">
            <div class="text-sm font-semibold text-text-base">No conversations yet</div>
            <p class="text-sm text-text-subtle mt-1">Start a channel or message a teammate to begin.</p>
        </div>
    </div>
@endsection
