@extends('layouts.trades')

@section('title', 'Edit Team Member')

@section('trades-content')
    @include('shared.team-members._form', ['mode' => 'edit', 'routePrefix' => 'tenant.trades.team'])
@endsection
