{{-- resources/views/organizations/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Organization')

@section('content')
    @php
        // Resolve tenant id (works with {tenant} as model or scalar)
        $tp = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;

        // Convenience accessors that work for array or model
        $orgName = data_get($organization, 'name', '—');
        $orgInd = data_get($organization, 'industry', '—');
        $orgLoc = data_get($organization, 'location', '—');
        $orgWebsite = data_get($organization, 'website');
        $orgPhone = data_get($organization, 'phone', '—');
        $orgNotes = data_get($organization, 'notes', '');
    @endphp

    <div class="container mx-auto px-4 py-6 space-y-6">

        {{-- Back link --}}
        <a href="{{ route('tenant.organizations.index', ['tenant' => $tenantId]) }}"
            class="inline-flex items-center text-blue-700 hover:text-blue-800">
            ← Back to Organizations
        </a>

        <section class="space-y-4">
            <h1 class="text-2xl font-semibold">{{ $orgName }}</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><strong>Industry:</strong> {{ $orgInd ?: '—' }}</div>
                <div><strong>Location:</strong> {{ $orgLoc ?: '—' }}</div>
                <div class="col-span-1 md:col-span-2">
                    <strong>Website:</strong>
                    @if (!empty($orgWebsite))
                        <a href="{{ $orgWebsite }}" class="text-blue-700 hover:underline" target="_blank" rel="noopener">
                            {{ $orgWebsite }}
                        </a>
                    @else
                        —
                    @endif
                </div>
                <div><strong>Phone:</strong> {{ $orgPhone ?: '—' }}</div>

                <div class="md:col-span-2">
                    <strong>Notes:</strong><br>
                    {!! nl2br(e($orgNotes ?? '')) !!}
                </div>
            </div>
        </section>

        {{-- Contacts --}}
        <section class="space-y-3">
            <h3 class="text-lg font-semibold">Contacts</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Email</th>
                            <th class="px-4 py-2">Phone</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            @php
                                $cid = data_get($client, 'id');
                                $first = data_get($client, 'firstName', data_get($client, 'first_name'));
                                $last = data_get($client, 'lastName', data_get($client, 'last_name'));
                                $cname = trim(($first ?? '') . ' ' . ($last ?? ''));
                                $cname = $cname !== '' ? $cname : '—';
                            @endphp
                            <tr class="border-t border-gray-200">
                                <td class="px-4 py-2">
                                    @if ($cid)
                                        <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantId, 'contact' => $cid]) }}"
                                            class="text-blue-700 hover:underline">
                                            {{ $cname }}
                                        </a>
                                    @else
                                        {{ $cname }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ data_get($client, 'email', '—') }}</td>
                                <td class="px-4 py-2">{{ data_get($client, 'phone', '—') }}</td>
                                <td class="px-4 py-2">
                                    {{ ucfirst((string) data_get($client, 'status', '')) ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">No contacts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Projects --}}
        <section class="space-y-3">
            <h3 class="text-lg font-semibold">Projects</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Start Date</th>
                            <th class="px-4 py-2">End Date</th>
                            <th class="px-4 py-2">Payments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                            @php
                                $pid = data_get($project, 'id');
                                $pname = data_get($project, 'project_name', data_get($project, 'name', '—'));
                                $pstat = data_get($project, 'status', '—');

                                $startRaw = data_get($project, 'start_date');
                                $endRaw = data_get($project, 'end_date');

                                $fmt = function ($raw) {
                                    if (empty($raw) || $raw === '0000-00-00 00:00:00') {
                                        return '—';
                                    }
                                    try {
                                        return \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
                                    } catch (\Throwable $e) {
                                        return '—';
                                    }
                                };

                                $payments = (array) data_get($project, 'payments', []);
                            @endphp
                            <tr class="border-t border-gray-200 align-top">
                                <td class="px-4 py-2">
                                    @if ($pid)
                                        <a href="{{ route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $pid]) }}"
                                            class="text-blue-700 hover:underline">
                                            {{ $pname }}
                                        </a>
                                    @else
                                        {{ $pname }}
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $pstat }}</td>
                                <td class="px-4 py-2">{{ $fmt($startRaw) }}</td>
                                <td class="px-4 py-2">{{ $fmt($endRaw) }}</td>
                                <td class="px-4 py-2">
                                    @if (!empty($payments))
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach ($payments as $payment)
                                                @php
                                                    $num = data_get($payment, 'payment_number', '—');
                                                    $amt = (float) data_get($payment, 'amount', 0);
                                                @endphp
                                                <li>#{{ $num }} - ${{ number_format($amt, 2) }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <em class="text-gray-500">No payments</em>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500">No projects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Total paid --}}
        <div class="text-sm">
            <strong>Total Paid Across All Projects:</strong>
            ${{ number_format((float) ($totalPaid ?? 0), 2) }}
        </div>

    </div>
@endsection
