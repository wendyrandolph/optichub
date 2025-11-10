@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Flash --}}
        @if (session('success_message'))
            <div class="rounded-lg px-4 py-3 text-sm bg-green-500/10 text-green-700 dark:text-green-300">
                {{ session('success_message') }}
            </div>
        @endif
        @if (session('error_message'))
            <div class="rounded-lg px-4 py-3 text-sm bg-status-danger/10 text-status-danger">
                {{ session('error_message') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg px-4 py-3 text-sm bg-status-danger/10 text-status-danger">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-text-base">Assign Task</h1>
            <a href="{{ route('tenant.tasks.index', ['tenant' => request()->route('tenant')]) }}"
                class="rounded-lg px-3 py-2 text-sm bg-surface-card/60 hover:bg-surface-card/90 text-text-base">
                Back to Task List
            </a>
        </div>

        {{-- Card --}}
        <div class="rounded-xl bg-surface-card/70 p-5">
            <form method="POST" action="{{ route('tenant.tasks.store', ['tenant' => request()->route('tenant')]) }}"
                enctype="multipart/form-data" class="grid gap-5">
                @csrf

                {{-- Title --}}
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Task Title</span>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required
                        class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                    @error('title')
                        <span class="text-status-danger text-xs">{{ $message }}</span>
                    @enderror
                </label>

                {{-- Description --}}
                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Description</span>
                    <textarea name="description" id="description" rows="4"
                        class="min-h-[96px] rounded-lg bg-surface-card text-text-base px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="text-status-danger text-xs">{{ $message }}</span>
                    @enderror
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Due Date --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Due Date</span>
                        <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" required
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        @error('due_date')
                            <span class="text-status-danger text-xs">{{ $message }}</span>
                        @enderror
                    </label>

                    {{-- Status (use values your board expects) --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Status</span>
                        <select name="status" id="status" required
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            @php $s = old('status'); @endphp
                            <option value="open" {{ $s === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $s === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ $s === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        @error('status')
                            <span class="text-status-danger text-xs">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Assign Type --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Assign Task To</span>
                        <select name="assign_type" id="assign_type" required
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            <option value="">Choose…</option>
                            <option value="admin" {{ old('assign_type') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="client" {{ old('assign_type') === 'client' ? 'selected' : '' }}>Client</option>
                        </select>
                        @error('assign_type')
                            <span class="text-status-danger text-xs">{{ $message }}</span>
                        @enderror
                    </label>

                    {{-- Hidden selected assign_id (populated by JS) --}}
                    <input type="hidden" name="assign_id" id="assign_id_final" value="{{ old('assign_id') }}">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Admin Select --}}
                    <label class="grid gap-1 text-sm" id="assign-admin" style="display:none;">
                        <span class="text-text-subtle">Choose Admin</span>
                        <select id="assign_id_admin"
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            <option value="">Choose Admin</option>
                            @foreach ($adminUsers as $user)
                                <option value="{{ $user->id ?? $user['id'] }}"
                                    {{ old('assign_type') === 'admin' && old('assign_id') == ($user->id ?? $user['id']) ? 'selected' : '' }}>
                                    {{ $user->username ?? ($user->name ?? ($user['username'] ?? $user['name'])) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Client Select --}}
                    <label class="grid gap-1 text-sm" id="assign-client" style="display:none;">
                        <span class="text-text-subtle">Choose Client</span>
                        <select id="assign_id_client"
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            <option value="">Choose Client</option>
                            @foreach ($clientUsers as $client)
                                <option value="{{ $client->id ?? $client['id'] }}"
                                    {{ old('assign_type') === 'client' && old('assign_id') == ($client->id ?? $client['id']) ? 'selected' : '' }}>
                                    {{ $client->client_name ?? ($client['client_name'] ?? ($client->name ?? $client['name'])) }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Project --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Project</span>
                        <select name="project_id" id="project_id" required
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            <option value="">Choose Project</option>
                            @foreach ($projects as $project)
                                @php
                                    $pid = $project->id ?? $project['id'];
                                    $pname = $project->project_name ?? ($project->name ?? 'Project');
                                    $cname =
                                        $project->client_name ??
                                        ($project['client_name'] ?? optional($project->client ?? null)->name);
                                    $clientId = $project->client_id ?? ($project['client_id'] ?? null);
                                @endphp
                                <option value="{{ $pid }}" data-client="{{ $clientId }}"
                                    {{ old('project_id') == $pid ? 'selected' : '' }}>
                                    {{ $pname }}{{ $cname ? ' — ' . $cname : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <span class="text-status-danger text-xs">{{ $message }}</span>
                        @enderror
                    </label>

                    {{-- Phase --}}
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Phase</span>
                        <select name="phase_id" id="phase_id" required
                            class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                            <option value="">Choose Phase</option>
                            @foreach ($phases as $phase)
                                @php
                                    $phid = $phase->id ?? $phase['id'];
                                    $phProjectId = $phase->project_id ?? ($phase['project_id'] ?? '');
                                    $phName = $phase->name ?? ($phase['name'] ?? 'Phase');
                                @endphp
                                <option value="{{ $phid }}" data-project="{{ $phProjectId }}"
                                    {{ old('phase_id') == $phid ? 'selected' : '' }}>
                                    {{ $phName }}
                                </option>
                            @endforeach
                        </select>
                        @error('phase_id')
                            <span class="text-status-danger text-xs">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                {{-- Optional fields --}}
                <div class="grid gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Upload File (Optional)</span>
                        <input type="file" name="upload_file" id="upload_file"
                            class="block w-full text-sm text-text-base file:mr-3 file:rounded-md
                        file:border-0 file:bg-surface-accent/70 file:px-3 file:py-2 file:text-sm">
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">External Form URL</span>
                            <input type="url" name="form_url" id="form_url" value="{{ old('form_url') }}"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="text-text-subtle">Image for Feedback (URL)</span>
                            <input type="url" name="feedback_image" id="feedback_image"
                                value="{{ old('feedback_image') }}"
                                class="h-10 rounded-lg bg-surface-card text-text-base px-3 text-sm focus:outline-none focus:ring-1 focus:ring-brand-primary">
                        </label>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-text-base">
                        <input type="checkbox" name="file_required" value="1"
                            class="h-4 w-4 rounded-sm accent-brand-primary" {{ old('file_required') ? 'checked' : '' }}>
                        Client must upload a file
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm text-text-base">
                        <input type="checkbox" name="agreement_signed" value="1"
                            class="h-4 w-4 rounded-sm accent-brand-primary"
                            {{ old('agreement_signed') ? 'checked' : '' }}>
                        Agreement Signed
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-2 pt-2">
                    <a href="{{ route('tenant.tasks.index', ['tenant' => request()->route('tenant')]) }}"
                        class="rounded-lg px-4 py-2 text-sm bg-surface-card/60 hover:bg-surface-card/90 text-text-base">
                        Cancel
                    </a>
                    <button type="submit"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-white bg-gradient-to-b from-brand-primary to-blue-700">
                        Assign Task
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const assignType = document.getElementById('assign_type');
            const adminWrap = document.getElementById('assign-admin');
            const clientWrap = document.getElementById('assign-client');
            const adminSelect = document.getElementById('assign_id_admin');
            const clientSelect = document.getElementById('assign_id_client');
            const assignIdFinal = document.getElementById('assign_id_final');
            const projectSelect = document.getElementById('project_id');
            const phaseSelect = document.getElementById('phase_id');

            function toggleAssign() {
                const type = assignType.value;
                if (type === 'admin') {
                    adminWrap.style.display = '';
                    clientWrap.style.display = 'none';
                    assignIdFinal.value = adminSelect.value || '';
                } else if (type === 'client') {
                    adminWrap.style.display = 'none';
                    clientWrap.style.display = '';
                    assignIdFinal.value = clientSelect.value || '';
                } else {
                    adminWrap.style.display = 'none';
                    clientWrap.style.display = 'none';
                    assignIdFinal.value = '';
                }
            }

            function filterPhasesByProject() {
                const pid = projectSelect?.value;
                if (!phaseSelect) return;
                [...phaseSelect.options].forEach(opt => {
                    if (!opt.value) return;
                    const matches = !opt.dataset.project || opt.dataset.project === pid;
                    opt.hidden = !matches;
                });
                if (phaseSelect.selectedOptions[0]?.hidden) phaseSelect.value = '';
            }

            assignType?.addEventListener('change', toggleAssign);
            adminSelect?.addEventListener('change', () => {
                if (assignType.value === 'admin') assignIdFinal.value = adminSelect.value || '';
            });
            clientSelect?.addEventListener('change', () => {
                if (assignType.value === 'client') assignIdFinal.value = clientSelect.value || '';
            });
            projectSelect?.addEventListener('change', filterPhasesByProject);

            // init
            toggleAssign();
            filterPhasesByProject();
        })();
    </script>
@endpush
