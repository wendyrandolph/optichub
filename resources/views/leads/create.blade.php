@extends('layouts.app')

@section('title', 'Add Lead')

@section('content')
    @php
        $tenantParam = $tenant->getKey();
    @endphp

    <div class="max-w-3xl mx-auto px-4 py-8">
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-heading">Add New Lead</h1>
            <p class="text-sm text-muted">Please fill out the required fields.</p>
        </header>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.leads.store', ['tenant' => $tenantParam]) }}"
            class="bg-white rounded-xl border border-border-default shadow-card p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-heading">Lead Name *</label>
                    <input id="name" name="name" type="text" required value="{{ old('name') }}"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-heading">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-heading">Phone</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-heading">Status</label>
                    <select id="status" name="status"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', 'new') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="source" class="block text-sm font-medium text-heading">Source</label>
                    <select id="source" name="source"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">—</option>
                        @foreach ($sources as $src)
                            <option value="{{ $src }}" @selected(old('source') === $src)>{{ ucfirst($src) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="owner_id" class="block text-sm font-medium text-heading">Owner</label>
                    <select id="owner_id" name="owner_id"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Unassigned</option>
                        @foreach ($owners as $o)
                            @php
                                $display =
                                    $o->username ?? trim(($o->first_name ?? '') . ' ' . ($o->last_name ?? '')) ?:
                                    $o->email;
                            @endphp
                            <option value="{{ $o->id }}" @selected((string) old('owner_id') === (string) $o->id)>{{ $display }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-heading">Notes</label>
                <textarea id="notes" name="notes" rows="4"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('tenant.leads.index', ['tenant' => $tenantParam]) }}"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>

                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Save Lead
                </button>
            </div>
        </form>
    </div>
@endsection
