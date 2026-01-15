@extends('layouts.trades')

@section('title', 'Team Members')

@section('trades-content')
    @include('shared.team-members._index', ['routePrefix' => 'tenant.trades.team'])
@endsection
