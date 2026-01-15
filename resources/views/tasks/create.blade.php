@extends('layouts.app')

@section('title', 'Add Task')

@section('content')
    @php
        // Expect these from controller:
        // $tenant, $projects, $assignees, $phases (optional), etc.
    @endphp

    <div class="oh-page">
        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs" style="color: rgb(var(--text-subtle));">
                    Tasks
                </div>
                <h1 class="text-2xl font-semibold tracking-tight" style="color: rgb(var(--text));">
                    Add Task
                </h1>
                <p class="mt-1 text-sm" style="color: rgb(var(--text-subtle));">
                    Assign work, attach context, and optionally request client input or approval.
                </p>
            </div>

            <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant]) }}" class="oh-btn">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                Back to Task List
            </a>
        </div>

        {{-- Form card --}}
        <section class="oh-card p-4 sm:p-6">
            <form method="POST" action="{{ route('tenant.tasks.store', ['tenant' => $tenant]) }}"
                enctype="multipart/form-data" class="oh-form">
                @csrf

                {{-- Top row: Title + Status --}}
                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="title">Task title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" class="oh-input"
                            placeholder="e.g. Finalize homepage hero layout" />
                        @error('title')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="status">Status</label>
                        <select id="status" name="status" class="oh-select">
                            @foreach (['open' => 'Open', 'in-progress' => 'In Progress', 'blocked' => 'Blocked', 'completed' => 'Completed'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', 'open') === $k)>{{ $v }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="due_date">Due date</label>
                        <input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}"
                            class="oh-input" />
                        @error('due_date')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                {{-- Assignment / project context --}}
                <div class="oh-form-grid">
                    <div class="oh-field">
                        <label class="oh-label" for="assign_type">Assign type</label>
                        <select id="assign_type" name="assign_type" class="oh-select">
                            <option value="">Choose…</option>
                            <option value="admin" @selected(old('assign_type') === 'admin')>Admin/Internal</option>
                            <option value="client" @selected(old('assign_type') === 'client')>Client</option>
                        </select>
                        @error('assign_type')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="assign_admin">Admin</label>
                        <select id="assign_admin" class="oh-select">
                            <option value="">Choose Admin</option>
                            @foreach ($adminUsers ?? [] as $admin)
                                @php
                                    $adminName = trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''));
                                    $adminLabel = $adminName !== '' ? $adminName : ($admin->username ?? $admin->email ?? 'User #' . $admin->id);
                                @endphp
                                <option value="{{ $admin->id }}" @selected((string) old('assign_id') === (string) $admin->id && old('assign_type') === 'admin')>
                                    {{ $adminLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="assign_client">Client</label>
                        <select id="assign_client" class="oh-select">
                            <option value="">Choose Client</option>
                            @foreach ($clientUsers ?? [] as $client)
                                @php
                                    $clientLabel = trim(($client->client_name ?? '') . ' ' . ($client->lastName ?? ''));
                                    $clientLabel = $clientLabel !== '' ? $clientLabel : 'Client #' . $client->id;
                                @endphp
                                <option value="{{ $client->id }}" @selected((string) old('assign_id') === (string) $client->id && old('assign_type') === 'client')>
                                    {{ $clientLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="project_id">Project</label>
                        <select id="project_id" name="project_id" class="oh-select">
                            <option value="">Choose project…</option>
                            @foreach ($projects ?? [] as $p)
                                <option value="{{ $p->id }}" @selected((string) old('project_id') === (string) $p->id)>
                                    {{ $p->project_name ?? ($p->name ?? 'Project #' . $p->id) }}
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
                            <option value="">Choose phase…</option>
                            @foreach ($phases ?? [] as $ph)
                                <option value="{{ $ph->id }}" @selected((string) old('phase_id') === (string) $ph->id)>
                                    {{ $ph->name ?? 'Phase #' . $ph->id }}
                                </option>
                            @endforeach
                        </select>
                        <p class="oh-help">Optional — keep work organized by phase.</p>
                        @error('phase_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="priority">Priority</label>
                        <select id="priority" name="priority" class="oh-select">
                            @foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('priority', 'normal') === $k)>{{ $v }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                {{-- Description + files/links --}}
                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="description">Description</label>
                        <textarea id="description" name="description" class="oh-textarea"
                            placeholder="Add details, acceptance criteria, links, etc.">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="upload">Upload file (optional)</label>
                        <input id="upload" name="upload" type="file" class="oh-input"
                            style="padding-top: 7px; padding-bottom: 7px;" />
                        <p class="oh-help">Add a spec, screenshot, or reference file.</p>
                        @error('upload')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="external_url">External form URL</label>
                        <input id="external_url" name="external_url" type="url" value="{{ old('external_url') }}"
                            class="oh-input" placeholder="https://..." />
                        @error('external_url')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="feedback_image_url">Image for feedback (URL)</label>
                        <input id="feedback_image_url" name="feedback_image_url" type="url"
                            value="{{ old('feedback_image_url') }}" class="oh-input" placeholder="https://..." />
                        <p class="oh-help">Used when the client needs to review/approve a specific visual.</p>
                        @error('feedback_image_url')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                {{-- Client-facing toggles --}}
                <div class="grid gap-3">
                    <label class="flex items-start gap-3 rounded-xl p-3 ring-1 ring-[rgb(var(--border)/.6)]"
                        style="background: rgb(var(--surface));">
                        <input type="checkbox" name="client_must_upload" value="1" class="mt-1"
                            @checked(old('client_must_upload')) />
                        <div>
                            <div class="text-sm font-semibold" style="color: rgb(var(--text));">Client must upload a file
                            </div>
                            <div class="oh-help">Great for “send your logo”, “upload content”, etc.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-xl p-3 ring-1 ring-[rgb(var(--border)/.6)]"
                        style="background: rgb(var(--surface));">
                        <input type="checkbox" name="requires_approval" value="1" class="mt-1"
                            @checked(old('requires_approval')) />
                        <div>
                            <div class="text-sm font-semibold" style="color: rgb(var(--text));">Requires approval</div>
                            <div class="oh-help">Shows in the client thread as an approval item.</div>
                        </div>
                    </label>
                </div>

                {{-- Sticky actions --}}
                <div class="oh-sticky-actions mt-6">
                    <div class="flex flex-col sm:flex-row gap-2 sm:justify-end">
                        <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant]) }}"
                            class="oh-btn w-full sm:w-auto justify-center">
                            Cancel
                        </a>

                        <button type="submit" class="oh-btn oh-btn--primary w-full sm:w-auto justify-center">
                            <i class="fa-solid fa-check mr-1.5"></i>
                            Save task
                        </button>
                    </div>
                </div>
                <input type="hidden" name="assign_id" id="assign_id" value="{{ old('assign_id') }}">
                <input type="hidden" name="user_id" id="assign_user_id" value="{{ old('user_id') }}">
            </form>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const assignType = document.getElementById('assign_type');
            const assignAdmin = document.getElementById('assign_admin');
            const assignClient = document.getElementById('assign_client');
            const assignId = document.getElementById('assign_id');
            const assignUserId = document.getElementById('assign_user_id');

            if (!assignType || !assignAdmin || !assignClient || !assignId || !assignUserId) return;

            const syncAssign = () => {
                const type = assignType.value;
                const adminVal = assignAdmin.value;
                const clientVal = assignClient.value;

                if (type === 'admin') {
                    assignId.value = adminVal || '';
                    assignUserId.value = adminVal || '';
                    assignAdmin.disabled = false;
                    assignClient.disabled = true;
                } else if (type === 'client') {
                    assignId.value = clientVal || '';
                    assignUserId.value = '';
                    assignAdmin.disabled = true;
                    assignClient.disabled = false;
                } else {
                    assignId.value = '';
                    assignUserId.value = '';
                    assignAdmin.disabled = true;
                    assignClient.disabled = true;
                }
            };

            assignType.addEventListener('change', syncAssign);
            assignAdmin.addEventListener('change', syncAssign);
            assignClient.addEventListener('change', syncAssign);
            syncAssign();
        });
    </script>
@endsection
