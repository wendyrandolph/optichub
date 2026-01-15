@extends('layouts.app')

@section('title', 'Add Team Member')

@section('content')
    @include('shared.team-members._form', ['mode' => 'create'])
@endsection
