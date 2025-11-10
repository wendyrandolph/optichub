@extends('layouts.app')

@section('title', 'Edit Team Member')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="container mx-auto p-4 max-w-2xl">
        <h1 class="text-2xl font-semibold mb-6">Edit Team Member</h1>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700">
                <strong>Fix the following:</strong>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('tenant.team-members.update', ['tenant' => $tenantParam, 'team_member' => $team_member->id]) }}"
            method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">First Name</label>
                    <input name="first_name" value="{{ old('first_name', $team_member->first_name) }}" required
                        class="w-full border rounded p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Last Name</label>
                    <input name="last_name" value="{{ old('last_name', $team_member->last_name) }}" required
                        class="w-full border rounded p-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $team_member->email) }}" required
                    class="w-full border rounded p-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select name="role" class="w-full border rounded p-2" required>
                        @php $role = old('role', $team_member->role); @endphp
                        <option value="employee" @selected($role === 'employee')>Employee</option>
                        <option value="admin" @selected($role === 'admin')>Admin</option>
                        <option value="contractor" @selected($role === 'contractor')>Contractor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Title (optional)</label>
                    <input name="title" value="{{ old('title', $team_member->title) }}"
                        class="w-full border rounded p-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Phone (optional)</label>
                <input name="phone" value="{{ old('phone', $team_member->phone) }}" class="w-full border rounded p-2" />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                @php $status = old('status', $team_member->status); @endphp
                <select name="status" class="w-full border rounded p-2" required>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn--primary">Update</button>
                <a href="{{ route('tenant.team-members.index', ['tenant' => $tenantParam]) }}"
                    class="btn btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
