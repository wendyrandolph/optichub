{{-- resources/views/admin/settings/api-keys.blade.php --}}
@extends('layouts.app')

@section('title', 'API Keys')

@section('content')
    @php
        $tenantParam = request()->route('tenant') ?? auth()->user()->tenant_id;
    @endphp

    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">API Keys</h1>

        @if (session('flash_success'))
            <div class="mb-4 p-3 rounded bg-green-50 text-green-800">
                {{ session('flash_success') }}
            </div>
        @endif

        {{-- Show the plain key ONLY ONCE after generation --}}
        @if (!empty($newPlainKey))
            <div class="mb-4 p-3 rounded bg-yellow-50 text-yellow-800">
                <strong>Copy this key now:</strong>
                <code class="bg-white px-2 py-1 rounded border">{{ $newPlainKey }}</code>
                <div class="text-sm text-yellow-700 mt-1">For security, it won’t be shown again.</div>
            </div>
        @endif

        <div class="flex items-center gap-3 mb-6">
            <form method="POST" action="{{ route('tenant.settings.api.generate', ['tenant' => $tenantParam]) }}">
                @csrf
                <button class="btn btn--brand" type="submit">
                    <i class="fa fa-key mr-1"></i> Generate New Key
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Last 4</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($keys as $key)
                        <tr>
                            <td class="px-4 py-2">{{ $key->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $key->key_last4 }}</td>
                            <td class="px-4 py-2 capitalize">{{ $key->status }}</td>
                            <td class="px-4 py-2 text-right">
                                @if ($key->status === 'active')
                                    <form method="POST"
                                        action="{{ route('tenant.settings.api.revoke', ['tenant' => $tenantParam, 'keyId' => $key->id]) }}"
                                        onsubmit="return confirm('Revoke this API key? This cannot be undone.');">
                                        @csrf
                                        <button class="btn btn--ghost text-red-600" type="submit">
                                            <i class="fa fa-ban mr-1"></i> Revoke
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 italic">revoked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-gray-500" colspan="4">No API keys yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
