@extends('layouts.app')

@section('title', 'Edit Team Member')

@section('content')
    @include('shared.team-members._form', ['mode' => 'edit'])
@endsection
