{{-- resources/views/clients/missing-client.blade.php --}}
@extends('layouts.portal')

@section('content')
    <div class="max-w-lg mx-auto mt-16 text-center">
        <h1 class="text-xl font-semibold mb-2 text-text-base">Portal not configured</h1>
        <p class="text-sm text-text-subtle">
            We couldn’t find a client record connected to your login yet.
            Please contact your account administrator.
        </p>
    </div>
@endsection
