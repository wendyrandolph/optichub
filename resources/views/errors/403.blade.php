@extends('layouts.app')

@section('title', 'Forbidden')

@section('content')
    <section class="section section--white text-center">
        <h1 class="text-3xl font-bold mb-4">403 — Forbidden</h1>
        <p class="text-gray-600 mb-8">Sorry, you are not allowed to view this page.</p>
        @php $homeUrl = auth()->check() ? url('/dashboard') : url('/'); @endphp
        <a href="{{ $homeUrl }}" class="btn btn--brand">Return Home</a>
    </section>
    @include('partials.footer')
