@extends('layouts.app')
@section('title', 'System Status')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">System Status</h1>
            <span class="text-xs text-gray-500">Version {{ $version }}</span>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($checks as $name => $info)
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium text-gray-800 dark:text-gray-200 capitalize">{{ $name }}</h2>
                        @if ($info['ok'])
                            <span class="inline-flex items-center text-xs text-emerald-600">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 mr-1"></span> OK
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs text-red-600">
                                <span class="h-2 w-2 rounded-full bg-red-500 mr-1"></span> Issue
                            </span>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $info['message'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
