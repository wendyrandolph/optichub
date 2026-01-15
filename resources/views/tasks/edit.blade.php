@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
    @php
        $tenantKey = $tenant;
        $statusOptions = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'blocked' => 'Blocked',
            'completed' => 'Completed',
        ];
        $priorityOptions = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'];
    @endphp

    <div class="oh-page">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs" style="color: rgb(var(--text-subtle));">Tasks</div>
                <h1 class="text-2xl font-semibold tracking-tight" style="color: rgb(var(--text));">
                    Edit Task
                </h1>
                <p class="mt-1 text-sm" style="color: rgb(var(--text-subtle));">
                    Update the task details, assignment, and status.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('tenant.tasks.show', ['tenant' => $tenantKey, 'task' => $task->id]) }}" class="oh-btn">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i>
                    Back to Task
                </a>
                <a href="{{ route('tenant.tasks.index', ['tenant' => $tenantKey]) }}" class="oh-btn">
                    All Tasks
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm mt-4">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="oh-card p-4 sm:p-6 mt-4">
            <form method="POST" action="{{ route('tenant.tasks.update', ['tenant' => $tenantKey, 'task' => $task->id]) }}"
                class="oh-form">
                @csrf
                @method('PUT')

                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="title">Task title</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}"
                            class="oh-input" />
                        @error('title')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="status">Status</label>
                        <select id="status" name="status" class="oh-select">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $task->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="due_date">Due date</label>
                        <input id="due_date" name="due_date" type="date"
                            value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}"
                            class="oh-input" />
                        @error('due_date')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                <div class="oh-form-grid">
                    <div class="oh-field">
                        <label class="oh-label" for="user_id">Assigned team member</label>
                        <select id="user_id" name="user_id" class="oh-select">
                            <option value="">Unassigned</option>
                            @foreach ($users ?? [] as $user)
                                @php
                                    $label = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                    $label = $label !== '' ? $label : ($user->username ?? $user->email ?? 'User #' . $user->id);
                                @endphp
                                <option value="{{ $user->id }}" @selected((string) old('user_id', $task->user_id) === (string) $user->id)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="contact_id">Assigned client</label>
                        <select id="contact_id" name="contact_id" class="oh-select">
                            <option value="">Unassigned</option>
                            @foreach ($clients ?? [] as $client)
                                @php
                                    $clientLabel = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''));
                                    $clientLabel = $clientLabel !== '' ? $clientLabel : 'Client #' . $client->id;
                                @endphp
                                <option value="{{ $client->id }}" @selected((string) old('contact_id', $task->contact_id) === (string) $client->id)>
                                    {{ $clientLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('contact_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="project_id">Project</label>
                        <select id="project_id" name="project_id" class="oh-select">
                            <option value="">No project</option>
                            @foreach ($projects ?? [] as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id', $task->project_id) === (string) $project->id)>
                                    {{ $project->project_name ?? 'Project #' . $project->id }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="phase_id">Phase</label>
                        <select id="phase_id" name="phase_id" class="oh-select">
                            <option value="">No phase</option>
                            @foreach ($phases ?? [] as $phase)
                                <option value="{{ $phase->id }}" @selected((string) old('phase_id', $task->phase_id) === (string) $phase->id)>
                                    {{ $phase->name ?? 'Phase #' . $phase->id }}
                                </option>
                            @endforeach
                        </select>
                        @error('phase_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="priority">Priority</label>
                        <select id="priority" name="priority" class="oh-select">
                            @foreach ($priorityOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', $task->priority ?? 'normal') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="hours_spent">Hours spent</label>
                        <input id="hours_spent" name="hours_spent" type="number" step="0.25" min="0"
                            value="{{ old('hours_spent', $task->hours_spent) }}" class="oh-input" />
                        @error('hours_spent')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="description">Description</label>
                        <textarea id="description" name="description" class="oh-textarea" rows="4">{{ old('description', $task->description) }}</textarea>
                        @error('description')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="flex items-start gap-3 rounded-xl p-3 ring-1 ring-[rgb(var(--border)/.6)]"
                        style="background: rgb(var(--surface));">
                        <input type="checkbox" name="requires_approval" value="1" class="mt-1"
                            @checked(old('requires_approval', (bool) $task->requires_approval)) />
                        <div>
                            <div class="text-sm font-semibold" style="color: rgb(var(--text));">Requires approval</div>
                            <div class="oh-help">Shows in the client thread as an approval item.</div>
                        </div>
                    </label>
                </div>

                <div class="oh-sticky-actions mt-6">
                    <div class="flex flex-col sm:flex-row gap-2 sm:justify-end">
                        <a href="{{ route('tenant.tasks.show', ['tenant' => $tenantKey, 'task' => $task->id]) }}"
                            class="oh-btn w-full sm:w-auto justify-center">
                            Cancel
                        </a>

                        <button type="submit" class="oh-btn oh-btn--primary w-full sm:w-auto justify-center">
                            <i class="fa-solid fa-check mr-1.5"></i>
                            Save changes
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection
