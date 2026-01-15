@extends('layouts.trades')

@section('title', 'Add Team Member')

@section('trades-content')
    @include('shared.team-members._form', ['mode' => 'create', 'routePrefix' => 'tenant.trades.team'])
@endsection
