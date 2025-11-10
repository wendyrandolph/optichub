@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
    <section class="section section--white text-center">
        <h1 class="text-3xl font-bold mb-4">404 — Page Not Found</h1>
        <p class="text-gray-600 mb-8">Sorry, the page you’re looking for doesn’t exist or may have been moved.</p>
        <a href="{{ url('/') }}" class="btn btn--brand">Return Home</a>
    </section>
@endsection
