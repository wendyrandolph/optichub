@extends('layouts.app')

@section('title', 'Add Contact')

@section('content')
    @php
        $tenantId = $tenant ?? (auth()->user()->tenant_id ?? null);
    @endphp

    <div class="container mx-auto p-4 max-w-2xl">
        <h1 class="text-2xl font-semibold mb-6">Add Contact</h1>

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

        <form action="{{ route('tenant.contacts.store', ['tenant' => $tenantId]) }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">First Name</label>
                    <input name="firstName" value="{{ old('firstName') }}" required class="w-full border rounded p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Last Name</label>
                    <input name="lastName" value="{{ old('lastName') }}" required class="w-full border rounded p-2" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border rounded p-2" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Phone (optional)</label>
                    <input name="phone" value="{{ old('phone') }}" class="w-full border rounded p-2" />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" class="w-full border rounded p-2">
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive"@selected(old('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Notes (optional)</label>
                <textarea name="notes" rows="4" class="w-full border rounded p-2">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn btn--primary">Save</button>
                <a href="{{ route('tenant.contacts.index', ['tenant' => $tenantId]) }}" class="btn btn--ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
