@extends('layouts.app')

@section('title', 'Add Team Member')

@section('content')
    @php
        $tenantParam = $tenant ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="container mx-auto p-4 max-w-2xl">
        <h1 class="text-2xl font-semibold mb-6">Add Team Member</h1>

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

        <form action="{{ route('tenant.team-members.store', ['tenant' => $tenantParam]) }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">First Name</label>
                    <input name="first_name" value="{{ old('first_name') }}" required class="w-full border rounded p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Last Name</label>
                    <input name="last_name" value="{{ old('last_name') }}" required class="w-full border rounded p-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border rounded p-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select name="role" class="w-full border rounded p-2" required>
                        <option value="employee" @selected(old('role') === 'employee')>Employee</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        <option value="contractor" @selected(old('role') === 'contractor')>Contractor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Title (optional)</label>
                    <input name="title" value="{{ old('title') }}" class="w-full border rounded p-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Phone (optional)</label>
                <input name="phone" value="{{ old('phone') }}" class="w-full border rounded p-2" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn--primary">Save</button>
                <a href="{{ route('tenant.team-members.index', ['tenant' => $tenantParam]) }}"
                    class="btn btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
