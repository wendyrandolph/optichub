@extends('layouts.app')

@section('title', 'Upgrade Your Plan')

@section('content')
    @php
        $u = auth()->user();
        $tenantId = $u->tenant_id ?? null; // should exist due to middleware, but safe guard
    @endphp

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-2">Upgrade your plan</h1>
        <p class="text-gray-600 mb-6">
            You’re currently on a trial. Choose a plan to keep everything running smoothly.
        </p>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ($plans as $plan)
                <div class="p-6 border rounded-xl bg-white">
                    <h3 class="text-lg font-medium">{{ $plan['name'] }}</h3>
                    <p class="text-3xl font-bold mt-2">
                        ${{ number_format($plan['price'] / 100, 0) }}
                        <span class="text-sm text-gray-500">/mo</span>
                    </p>
                    <ul class="mt-4 text-sm text-gray-600 space-y-2">
                        @foreach ($plan['features'] as $f)
                            <li>{{ $f }}</li>
                        @endforeach
                    </ul>

                    {{-- Replace this with your checkout/subscribe route --}}
                    <form class="mt-4" method="POST"
                        action="{{ route('tenant.settings.billing', ['tenant' => $tenantId]) }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan['code'] }}">
                        <button type="submit" class="btn btn--brand">Choose {{ $plan['name'] }}</button>
                    </form>
                </div>
            @endforeach
        </div>

        <p class="text-sm text-gray-500 mt-6">
            Questions? <a href="/contact?topic=billing" class="text-blue-600 underline">Contact support</a>.
        </p>
    </div>
@endsection
