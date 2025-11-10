@extends('layouts.app')

@section('title', 'Tenant Dashboard')

@section('content')
    @php
        // ====== SAFE DEFAULTS / CONTEXT ======
        // Ensure we always have a tenant param for route links
        $tenantParam = $tenant ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);

    @endphp
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold mb-4">Tenant Dashboard</h1>
        <p>Tenant Dashboard Coming soon</p>
    </div>
@endsection
