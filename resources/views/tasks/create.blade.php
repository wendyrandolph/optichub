@extends('layouts.app')

@section('title', 'Add Task')

@section('content')
    @php
        // Expect these from controller:
        // $tenant, $projects, $assignees, $phases (optional), etc.
        $statusOptions = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'blocked' => 'Blocked',
            'completed' => 'Completed',
        ];

        $priorityOptions = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'];

        $teamMembers = $teamMembers ?? ($adminUsers ?? []);
        $clients = $clients ?? ($clientUsers ?? []);
        $prefillContactId = request('contact');
        $currentAssignType = old('assign_type', $prefillContactId ? 'client' : '');
        $prefillContactId = old('contact_id', $prefillContactId);
    @endphp

    <div class="oh-page">
        {{-- Header --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.08em] text-text-subtle">Tasks</div>
                <h1 class="text-2xl font-semibold tracking-tight text-text-base">Add Task</h1>
                <p class="mt-1 text-sm text-text-subtle">
                    Assign work, attach context, and optionally request client input or approval.
                </p>
            </div>

            @if ($prefillContactId)
                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenant, 'contact' => $prefillContactId]) }}" class="oh-btn">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i>
                    Back to Client Details
                </a>
            @else
                <a href="{{ route('tenant.tasks.index', ['tenant' => $tenant]) }}" class="oh-btn">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i>
                    Back to Task List
                </a>
            @endif
        </div>

        {{-- Form card --}}
        <section class="oh-card p-4 sm:p-6 lg:max-w-4xl lg:mx-auto">
            <form method="POST" action="{{ route('tenant.tasks.store', ['tenant' => $tenant]) }}"
                enctype="multipart/form-data" class="oh-form">
                @csrf

                {{-- Top row: Title + Status + Due Date --}}
                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="title">Task title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" class="oh-input"
                            placeholder="e.g. Finalize homepage hero layout" />
                        @error('title')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="description">Description</label>
                        <textarea id="description" name="description" class="oh-textarea"
                            placeholder="Add details, acceptance criteria, links, etc.">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
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
                        <label class="oh-label" for="status">Status</label>
                        <select id="status" name="status" class="oh-select">
                            @foreach ($statusOptions as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', 'todo') === $k)>{{ $v }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
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
                            @foreach ($priorityOptions as $k => $v)
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

                {{-- Assignment / project context --}}
                <div class="oh-form-grid">
                    <div class="lg:col-span-2 grid gap-4 lg:grid-cols-2">
                        <div class="oh-field">
                            <label class="oh-label" for="assign_type">Team Member or Client</label>
                            <select id="assign_type" name="assign_type" class="oh-select">
                                <option value="">Choose…</option>
                                <option value="admin" @selected($currentAssignType === 'admin')>Team member</option>
                                <option value="client" @selected($currentAssignType === 'client')>Client</option>
                            </select>
                            @error('assign_type')
                                <p class="oh-help text-status-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="oh-field" data-assign-admin>
                            <label class="oh-label" for="assign_id">Assigned team member</label>
                            <input type="hidden" name="assign_id" id="assign_id_client" value="" disabled />
                            <select id="assign_id" name="assign_id" class="oh-select">
                                <option value="">Unassigned</option>
                                @foreach ($teamMembers as $member)
                                    @php
                                        $label = trim(
                                            ($member->firstName ?? ($member->first_name ?? '')) .
                                                ' ' .
                                                ($member->lastName ?? ($member->last_name ?? '')),
                                        );
                                        $label =
                                            $label !== '' ? $label : $member->email ?? 'Team member #' . $member->id;
                                    @endphp
                                    <option value="{{ $member->id }}" @selected((string) old('assign_id') === (string) $member->id)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('assign_id')
                                <p class="oh-help text-status-danger">{{ $message }}</p>
                            @enderror
                            <p class="oh-help">Choose either a team member or a client (not both).</p>
                        </div>

                        <div class="oh-field lg:col-start-2 hidden" data-assign-client>
                            <label class="oh-label" for="contact_id">Assigned client</label>
                            <select id="contact_id" name="contact_id" class="oh-select">
                                <option value="">Unassigned</option>
                                @foreach ($clients as $client)
                                    @php
                                        $clientLabel = trim(
                                            ($client->firstName ?? ($client->client_name ?? '')) .
                                                ' ' .
                                                ($client->lastName ?? ''),
                                        );
                                        $clientLabel = $clientLabel !== '' ? $clientLabel : 'Client #' . $client->id;
                                    @endphp
                                    <option value="{{ $client->id }}" @selected((string) $prefillContactId === (string) $client->id)>
                                        {{ $clientLabel }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_id')
                                <p class="oh-help text-status-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="oh-field lg:col-span-2">
                            <label class="oh-label" for="assigned_user_id">Schedule assignee</label>
                            <select id="assigned_user_id" name="assigned_user_id" class="oh-select">
                                <option value="">Unassigned</option>
                                @foreach ($assignees as $assignee)
                                    @php
                                        $assigneeName = trim(($assignee->first_name ?? '') . ' ' . ($assignee->last_name ?? ''));
                                        $assigneeName = $assigneeName !== '' ? $assigneeName : ($assignee->email ?? 'User #' . $assignee->id);
                                    @endphp
                                    <option value="{{ $assignee->id }}" @selected((string) old('assigned_user_id', old('user_id')) === (string) $assignee->id)>
                                        {{ $assigneeName }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="oh-help">Used for My Schedule and planning views.</p>
                            @error('assigned_user_id')
                                <p class="oh-help text-status-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="oh-field">
                            <label class="oh-label" for="estimated_minutes">Estimated minutes</label>
                            <input id="estimated_minutes" name="estimated_minutes" type="number" min="0" step="5"
                                value="{{ old('estimated_minutes') }}" class="oh-input">
                            <p class="oh-help">Planning only. Actual time comes from time entries.</p>
                            @error('estimated_minutes')
                                <p class="oh-help text-status-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="oh-divider my-2"></div>

                {{-- Description + files/links --}}
                <div class="oh-form-grid">


                    <div class="oh-field">
                        <label class="oh-label" for="upload">Upload file (optional)</label>
                        <div class="flex flex-wrap items-center gap-3">
                            <input id="upload" name="upload" type="file" class="sr-only" />
                            <label for="upload"
                                class="inline-flex items-center rounded-md border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-3 py-2 text-sm font-medium text-[rgb(var(--text))] shadow-sm hover:bg-[rgb(var(--surface-muted))] focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgba(var(--ui-primary),0.35)] cursor-pointer">
                                Choose file
                            </label>
                            <span id="uploadFileName" class="text-sm text-text-subtle">No file chosen</span>
                        </div>
                        <p class="oh-help">Add a spec, screenshot, or reference file.</p>
                        @error('upload')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="external_url">External form URL</label>
                        <input id="external_url" name="external_url" type="url" value="{{ old('external_url') }}"
                            class="oh-input" placeholder="https://..." />
                        <p class="oh-help">If you have a form or external link for this task.</p>
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
                <div class="grid gap-3 lg:grid-cols-2">
                    <label
                        class="flex items-start gap-3 rounded-xl p-3  border border-[rgb(var(--border))] bg-surface-card">
                        <input type="checkbox" name="client_must_upload" value="1" class="mt-1"
                            @checked(old('client_must_upload')) />
                        <div>
                            <div class="text-sm font-semibold text-text-base">Client must upload a file</div>
                            <div class="oh-help">Great for “send your logo”, “upload content”, etc.</div>
                        </div>
                    </label>

                    <label
                        class="flex items-start gap-3 rounded-xl p-3 border border-[rgb(var(--border))] bg-surface-card">
                        <input type="checkbox" name="requires_approval" value="1" class="mt-1"
                            @checked(old('requires_approval')) />
                        <div>
                            <div class="text-sm font-semibold text-text-base">Requires approval</div>
                            <div class="oh-help">Shows in the client thread as an approval item.</div>
                        </div>
                    </label>
                    <div class="oh-field">
                        <label class="oh-label" for="due_date">Due date</label>
                        <input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}"
                            class="oh-input" />
                        @error('due_date')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-6 pt-4 border-t border-border-default/60">
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
            </form>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const assignType = document.getElementById('assign_type');
            const memberSelect = document.getElementById('assign_id');
            const clientSelect = document.getElementById('contact_id');
            const clientAssignId = document.getElementById('assign_id_client');
            const adminField = document.querySelector('[data-assign-admin]');
            const clientField = document.querySelector('[data-assign-client]');
            const uploadInput = document.getElementById('upload');
            const uploadName = document.getElementById('uploadFileName');

            const syncAssign = () => {
                if (!assignType || !memberSelect || !clientSelect || !clientAssignId || !adminField || !
                    clientField) return;

                const mode = assignType.value;
                if (mode === 'client') {
                    adminField.classList.add('hidden');
                    clientField.classList.remove('hidden');
                    memberSelect.value = '';
                    memberSelect.disabled = true;
                    clientAssignId.disabled = false;
                    clientAssignId.value = clientSelect.value || '';
                } else if (mode === 'admin') {
                    adminField.classList.remove('hidden');
                    clientField.classList.add('hidden');
                    clientSelect.value = '';
                    clientAssignId.disabled = true;
                    clientAssignId.value = '';
                    memberSelect.disabled = false;
                } else {
                    adminField.classList.add('hidden');
                    clientField.classList.add('hidden');
                    memberSelect.value = '';
                    clientSelect.value = '';
                    memberSelect.disabled = true;
                    clientAssignId.disabled = true;
                    clientAssignId.value = '';
                }
            };

            const syncUploadName = () => {
                if (!uploadInput || !uploadName) return;
                uploadName.textContent = uploadInput.files?.[0]?.name || 'No file chosen';
            };

            assignType?.addEventListener('change', syncAssign);
            clientSelect?.addEventListener('change', () => {
                if (assignType.value === 'client') {
                    clientAssignId.value = clientSelect.value || '';
                }
            });

            uploadInput?.addEventListener('change', syncUploadName);

            syncAssign();
            syncUploadName();
        });
    </script>
@endsection
