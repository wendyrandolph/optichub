@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'tenant.invoices';
    @endphp
    @include('shared.invoices.index', ['routePrefix' => $routePrefix])
@endsection
