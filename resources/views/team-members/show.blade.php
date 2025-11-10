@extends('layouts.app')

@section('title', 'Team Member')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="container mx-auto p-4 max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">
                {{ $team_member->full_name ?? $team_member->first_name . ' ' . $team_member->last_name }}</h1>

            <div class="space-x-2">
                <a href="{{ route('tenant.team-members.edit', ['tenant' => $tenantParam, 'team_member' => $team_member->id]) }}"
                    class="btn btn--primary">Edit</a>

                <a href="{{ route('tenant.team-members.index', ['tenant' => $tenantParam]) }}" class="btn btn--ghost">Back</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow p-6 space-y-3">
            <div><strong>Title:</strong> {{ $team_member->title ?? '—' }}</div>
            <div><strong>Email:</strong> {{ $team_member->email }}</div>
            <div><strong>Phone:</strong> {{ $team_member->phone ?? '—' }}</div>
            <div><strong>Role:</strong> {{ ucfirst($team_member->role) }}</div>
            <div>
                <strong>Status:</strong>
                <span
                    class="inline-block px-2 py-1 rounded-md text-xs
                {{ $team_member->status === 'active' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                    {{ ucfirst($team_member->status) }}
                </span>
            </div>
            <div class="text-sm text-gray-500 pt-2">
                Created: {{ $team_member->created_at?->format('M j, Y g:ia') ?? '—' }} •
                Updated: {{ $team_member->updated_at?->format('M j, Y g:ia') ?? '—' }}
            </div>
        </div>
    </div>
@endsection
