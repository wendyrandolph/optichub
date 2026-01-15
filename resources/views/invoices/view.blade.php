@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'tenant.invoices';
    @endphp
    @include('shared.invoices.view', ['routePrefix' => $routePrefix])
@endsection
