@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold text-indigo-700 mb-4">{{ $title }}</h1>
        <p>This would show the detailed report for ID: {{ $reportId }}</p>
        <a href="{{ route('admin.reports') }}" class="text-indigo-600 hover:underline">← Back to all reports</a>
    </div>
@endsection
