@extends('layouts.app')

@section('title', 'Team Members')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="container mx-auto p-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-semibold">Team Members</h1>
            @if ($tenantParam)
                <a href="{{ route('tenant.team-members.create', ['tenant' => $tenantParam]) }}" class="btn btn-add">
                    <i class="fa-solid fa-plus mr-1"></i> Add Team Member
                </a>
            @endif
        </div>

        @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="table-wrapper">
            <table class="w-full table-auto table-opportunities">
                <thead>
                    <tr>
                        <th class="text-left">Name</th>
                        <th class="text-left">Title</th>
                        <th class="text-left">Email</th>
                        <th class="text-left">Phone</th>
                        <th class="text-left">Role</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $m)
                        <tr>
                            <td>{{ $m->full_name ?? $m->first_name . ' ' . $m->last_name }}</td>
                            <td>{{ $m->title ?? '—' }}</td>
                            <td>{{ $m->email }}</td>
                            <td>{{ $m->phone ?? '—' }}</td>
                            <td>{{ ucfirst($m->role) }}</td>
                            <td>
                                <span
                                    class="inline-block px-2 py-1 rounded-md text-xs
                                {{ $m->status === 'active' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                                    {{ ucfirst($m->status) }}
                                </span>
                            </td>
                            <td class="space-x-2">
                                <a class="text-blue-600 hover:underline"
                                    href="{{ route('tenant.team-members.show', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}">
                                    View
                                </a>
                                <a class="text-blue-600 hover:underline"
                                    href="{{ route('tenant.team-members.edit', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}">
                                    Edit
                                </a>
                                <form
                                    action="{{ route('tenant.team-members.destroy', ['tenant' => $tenantParam, 'team_member' => $m->id]) }}"
                                    method="POST" style="display:inline;"
                                    onsubmit="return confirm('Delete this team member?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-6">No team members yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
