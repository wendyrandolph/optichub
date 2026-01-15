@extends('layouts.trades')

@section('title', 'Show Team Member')

@section('trades-content')
    @include('shared.team-members._show', ['routePrefix' => 'tenant.trades.team'])
@endsection
