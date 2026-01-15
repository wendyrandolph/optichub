@extends('layouts.app')

@section('title', 'Payments')

@section('content')
    @include('shared.settings.payments._index', [
        'tenant' => $tenant,
        'manualMethods' => $manualMethods,
    ])
@endsection
