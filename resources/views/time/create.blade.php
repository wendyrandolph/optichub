@extends('layouts.app')

@section('title', 'Log Time')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8">
        {{-- Back Link --}}
        <div class="mb-6">
            <a href="{{ route('tenant.time.index', ['tenant' => request()->route('tenant')]) }}"
                class="inline-flex items-center text-sm text-blue-700">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Time Log
            </a>
        </div>

        {{-- Header --}}
        <header class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-text-base">Log Time Entry</h1>
            <p class="text-gray-600 dark:text-text-subtle mt-1">Record your work hours with an optional project or task.</p>
        </header>

        {{-- Flash Messages --}}
        @if (session('success_message'))
            <div
                class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 p-3 dark:bg-green-900/30 dark:text-green-300">
                {{ session('success_message') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                class="mb-4 rounded-md bg-red-50 border border-red-200 text-red-800 p-3 dark:bg-red-900/30 dark:text-red-300">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Card --}}
        <div
            class="rounded-xl bg-white dark:bg-surface-card shadow-card border border-gray-200 dark:border-border-default p-6">
            <form method="POST" action="{{ route('tenant.time.store', ['tenant' => request()->route('tenant')]) }}"
                class="space-y-6">
                @csrf

                {{-- User --}}
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-text-base">User</label>
                    <select name="user_id" id="user_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500"
                        required>
                        <option value="">Select User</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name ?? $user->username }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date --}}
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-text-base">Date</label>
                    <input type="date" name="date" id="date" value="{{ old('date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                {{-- Project --}}
                <div>
                    <label for="project_id"
                        class="block text-sm font-medium text-gray-700 dark:text-text-base">Project</label>
                    <select name="project_id" id="project_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name ?? $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Task --}}
                <div>
                    <label for="task_id" class="block text-sm font-medium text-gray-700 dark:text-text-base">Task</label>
                    <select name="task_id" id="task_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Task</option>
                        @foreach ($tasks as $task)
                            <option value="{{ $task->id }}">{{ $task->title }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Time Inputs --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-text-base">Start
                            Time</label>
                        <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-text-base">End
                            Time</label>
                        <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="hours" class="block text-sm font-medium text-gray-700 dark:text-text-base">Total
                            Hours</label>
                        <input type="number" name="hours" id="hours" step="0.01" value="{{ old('hours') }}"
                            readonly required
                            class="mt-1 block w-full rounded-md bg-gray-100 dark:bg-surface-accent/40 border-gray-300 dark:border-border-default dark:text-text-base">
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label for="description"
                        class="block text-sm font-medium text-gray-700 dark:text-text-base">Description</label>
                    <textarea name="description" id="description" rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-border-default dark:bg-surface-card dark:text-text-base focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>

                {{-- Submit --}}
                <div class="pt-4">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-5 py-2 rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-brand-primary dark:hover:bg-blue-600 transition">
                        <i class="fa-solid fa-save mr-2"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const start = document.getElementById('start_time');
            const end = document.getElementById('end_time');
            const hours = document.getElementById('hours');

            function updateHours() {
                if (start.value && end.value) {
                    const startTime = new Date(`1970-01-01T${start.value}:00`);
                    const endTime = new Date(`1970-01-01T${end.value}:00`);
                    const diff = (endTime - startTime) / (1000 * 60 * 60);
                    hours.value = diff > 0 ? diff.toFixed(2) : '';
                }
            }

            start.addEventListener('change', updateHours);
            end.addEventListener('change', updateHours);
        });
    </script>
@endpush
