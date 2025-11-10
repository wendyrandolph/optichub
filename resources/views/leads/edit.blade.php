@extends('layouts.app')
@section('title', 'Edit Lead')

@section('content')
    @php $tenantParam = $tenant->getKey(); @endphp

    <div class="max-w-3xl mx-auto px-4 py-8">
        <header class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-heading">Edit Lead</h1>
            <a href="{{ route('tenant.leads.show', ['tenant' => $tenantParam, 'lead' => $lead]) }}"
                class="text-sm text-blue-600 hover:text-blue-800">Back to lead</a>
        </header>

        <form method="POST" action="{{ route('tenant.leads.update', ['tenant' => $tenantParam, 'lead' => $lead]) }}"
            class="bg-white rounded-xl border border-border-default shadow-card p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- same grid as create, but values from $lead --}}
            {{-- name --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-heading">Lead Name *</label>
                    <input name="name" type="text" required value="{{ old('name', $lead->name) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-heading">Email</label>
                    <input name="email" type="email" value="{{ old('email', $lead->email) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-heading">Phone</label>
                    <input name="phone" type="text" value="{{ old('phone', $lead->phone) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-heading">Status</label>
                    <select name="status"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', $lead->status) === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-heading">Source</label>
                    <select name="source"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">—</option>
                        @foreach ($sources as $src)
                            <option value="{{ $src }}" @selected(old('source', $lead->source) === $src)>{{ ucfirst($src) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-heading">Owner</label>
                    <select name="owner_id"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Unassigned</option>
                        @foreach ($owners as $o)
                            @php
                                $display =
                                    $o->username ?? trim(($o->first_name ?? '') . ' ' . ($o->last_name ?? '')) ?:
                                    $o->email;
                            @endphp
                            <option value="{{ $o->id }}" @selected((string) old('owner_id', $lead->owner_id) === (string) $o->id)>{{ $display }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-heading">Notes</label>
                <textarea name="notes" rows="4"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">{{ old('notes', $lead->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('tenant.leads.show', ['tenant' => $tenantParam, 'lead' => $lead]) }}"
                    class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Update Lead
                </button>
            </div>
        </form>
    </div>
@endsection
