{{-- resources/views/time/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Time Entry')

@section('content')
    @php
        $tenantId = $tenant->id ?? (request()->route('tenant') ?? auth()->user()->tenant_id);
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-2">
            <a href="{{ route('tenant.time.index', ['tenant' => $tenantId]) }}"
                class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base relative">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to time entries
                <span class="absolute left-0 bottom-[-3px] h-0.5 w-0 bg-[rgb(var(--brand-accent))] opacity-80 transition-all duration-200 group-hover:w-full"></span>
            </a>
            <h1 class="text-2xl font-semibold text-text-base">Edit Time Entry</h1>
            <p class="text-sm text-text-subtle">Update logged hours for this project/task.</p>
        </div>

        {{-- Form card --}}
        <div class="oh-card border border-border-default shadow-sm rounded-2xl">
            <form method="POST" action="{{ route('tenant.time.update', ['tenant' => $tenantId, 'entry' => $entry->id]) }}" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Project <span class="text-rose-500">*</span></span>
                        <select name="project_id" class="oh-select h-10" required>
                            @foreach ($projects ?? [] as $p)
                                <option value="{{ $p->id }}" @selected($entry->project_id == $p->id)>{{ $p->name ?? $p->project_name ?? 'Unnamed project' }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Task (optional)</span>
                        <select name="task_id" class="oh-select h-10" id="edit-task-select" data-project="{{ $entry->project_id }}">
                            <option value="">Select task</option>
                        </select>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Date</span>
                        <input type="date" name="date" value="{{ optional($entry->date ?? $entry->start_time)->toDateString() ?? now()->toDateString() }}"
                            class="oh-input h-10">
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Hours <span class="text-rose-500">*</span></span>
                        <input type="number" step="0.01" min="0.01" name="hours" value="{{ $entry->hours }}"
                            class="oh-input h-10" required>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Hourly rate (optional)</span>
                        <input type="number" step="0.01" min="0" name="hourly_rate" value="{{ $entry->hourly_rate }}"
                            class="oh-input h-10" placeholder="Inherit project rate if blank">
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="text-text-subtle">Member</span>
                        <select name="user_id" class="oh-select h-10">
                            @foreach ($users ?? [] as $m)
                                @php
                                    $label = trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? '')) ?: ($m->username ?? 'User');
                                @endphp
                                <option value="{{ $m->id }}" @selected($entry->user_id == $m->id)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm mt-6">
                        <input type="hidden" name="billable" value="0">
                        <input type="checkbox" name="billable" value="1" class="oh-input h-4 w-4" @checked($entry->billable)>
                        <span class="text-text-base">Billable</span>
                    </label>
                </div>

                <label class="grid gap-1 text-sm">
                    <span class="text-text-subtle">Notes</span>
                    <textarea name="notes" rows="3" class="oh-input" placeholder="Optional notes">{{ old('notes', $entry->notes) }}</textarea>
                </label>

                <div class="flex justify-end gap-2 pt-4">
                    <a href="{{ route('tenant.time.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
                    <button type="submit" class="oh-btn oh-btn--primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection

    @push('scripts')
        @php
            $tasksPayload = ($tasks ?? collect())->map(function ($t) {
                return [
                    'id' => $t->id,
                    'title' => $t->title,
                    'project_id' => $t->project_id,
                ];
            });
        @endphp
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const taskSelect = document.getElementById('edit-task-select');
                const projectSelect = document.querySelector('select[name="project_id"]');
                const tasks = @json($tasksPayload);

                function populateTasks(pid) {
                    if (!taskSelect) return;
                    const prev = taskSelect.dataset.selected || '{{ $entry->task_id }}';
                    taskSelect.innerHTML = '<option value="">Select task</option>';
                    const projectId = pid ? Number(pid) : null;
                    tasks
                        .filter(t => projectId && Number(t.project_id) === projectId)
                        .forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.id;
                            opt.textContent = t.title;
                            taskSelect.appendChild(opt);
                        });
                    if (prev && [...taskSelect.options].some(o => o.value === prev)) {
                        taskSelect.value = prev;
                    }
                }

                populateTasks(projectSelect?.value);
                projectSelect?.addEventListener('change', e => populateTasks(e.target.value));
            });
        </script>
    @endpush
