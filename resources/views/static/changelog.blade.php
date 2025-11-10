@extends('layouts.app')
@section('title', 'Changelog')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Changelog</h1>
            <span class="text-xs text-gray-500">Version {{ config('app.version', '1.0.0') }}</span>
        </div>

        <div class="mt-8 space-y-8">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">[Unreleased]</h2>
                <ul class="mt-2 list-disc pl-5 text-sm text-gray-600 dark:text-gray-300">
                    <li>Stub entry. Add notes as you release.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
