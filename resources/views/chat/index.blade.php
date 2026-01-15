@extends('layouts.app')

@section('title', 'Team Chat')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="oh-page space-y-6">
        <div class="flex flex-col gap-2">
            <p class="text-[11px] uppercase tracking-wider text-text-subtle">Communication</p>
            <h1 class="text-2xl font-semibold text-text-base">Team Chat</h1>
            <p class="text-sm text-text-subtle">Tenant-wide and project channels.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="oh-card border border-border-default/70 shadow-card">
                <h2 class="text-sm font-semibold text-text-base mb-3">Channels</h2>
                <div class="space-y-2">
                    @foreach ($channels as $channel)
                        @php $count = (int) ($unread[$channel->id] ?? 0); @endphp
                        <a href="{{ route('tenant.chat.show', ['tenant' => $tenantId, 'channel' => $channel->id]) }}"
                            class="flex items-center justify-between rounded-lg border border-border-default/60 px-3 py-2 hover:bg-surface-accent/40">
                            <span class="text-sm text-text-base">{{ $channel->name }}</span>
                            @if ($count > 0)
                                <span class="oh-pill oh-pill--info">{{ $count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="oh-card border border-border-default/70 shadow-card">
                <h2 class="text-sm font-semibold text-text-base mb-3">Project channels</h2>
                <div class="space-y-2">
                    @php
                        $projects = \App\Models\Project::query()
                            ->where('tenant_id', $tenantId)
                            ->whereNotIn('status', ['completed', 'cancelled', 'closed'])
                            ->orderBy('project_name')
                            ->get();
                    @endphp
                    @forelse ($projects as $project)
                        <a href="{{ route('tenant.chat.project', ['tenant' => $tenantId, 'project' => $project->id]) }}"
                            class="flex items-center justify-between rounded-lg border border-border-default/60 px-3 py-2 hover:bg-surface-accent/40">
                            <span class="text-sm text-text-base">{{ $project->project_name }}</span>
                            <i class="fa-solid fa-arrow-right text-[11px] text-text-subtle"></i>
                        </a>
                    @empty
                        <p class="text-sm text-text-subtle">No active projects yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
