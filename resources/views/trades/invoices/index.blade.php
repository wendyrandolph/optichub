@extends('layouts.trades')

@section('title', 'Invoices')

@section('trades-content')
    @php
        $routePrefix = $routePrefix ?? 'tenant.trades.invoices';
    @endphp
    @include('shared.invoices.index', ['routePrefix' => $routePrefix])
@endsection
