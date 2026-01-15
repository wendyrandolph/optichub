@extends('layouts.trades')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('trades-content')
    @php
        $routePrefix = $routePrefix ?? 'tenant.trades.invoices';
    @endphp
    @include('shared.invoices.view', ['routePrefix' => $routePrefix])
@endsection
