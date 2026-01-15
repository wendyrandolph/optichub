@extends('layouts.trades')

@section('title', 'Payments')

@section('trades-content')
    @include('shared.settings.payments._index', [
        'tenant' => $tenant,
        'manualMethods' => $manualMethods,
    ])
@endsection
