@extends('layouts.app')

@section('title', 'New Meeting')

@section('content')
    @php
        $tenantKey = $tenantId;
    @endphp

    <div class="oh-page">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.08em] text-text-subtle">Calendar</div>
                <h1 class="text-2xl font-semibold tracking-tight text-text-base">New Meeting</h1>
                <p class="text-sm text-text-subtle">Add a meeting to the schedule without creating a task.</p>
            </div>
            <a href="{{ route('tenant.calendar.index', ['tenant' => $tenantKey]) }}" class="oh-btn">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                Back to Calendar
            </a>
        </div>

        <section class="oh-card p-4 sm:p-6 lg:max-w-4xl lg:mx-auto">
            <form method="POST" action="{{ route('tenant.meetings.store', ['tenant' => $tenantKey]) }}" class="oh-form">
                @csrf

                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="title">Meeting title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" class="oh-input"
                            placeholder="e.g. Quarterly planning call" required />
                        @error('title')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="start_at">Start</label>
                        <input id="start_at" name="start_at" type="datetime-local"
                            value="{{ old('start_at') }}" class="oh-input" required />
                        @error('start_at')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="end_at">End</label>
                        <input id="end_at" name="end_at" type="datetime-local"
                            value="{{ old('end_at') }}" class="oh-input" />
                        <p class="oh-help">Optional. Defaults to one hour.</p>
                        @error('end_at')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                <div class="oh-form-grid">
                    <div class="oh-field">
                        <label class="oh-label" for="member_id">Team member</label>
                        <select id="member_id" name="member_id" class="oh-select">
                            <option value="">Unassigned</option>
                            @foreach ($members ?? [] as $member)
                                @php
                                    $label = trim(($member->firstName ?? '') . ' ' . ($member->lastName ?? ''));
                                    $label = $label !== '' ? $label : ($member->email ?? 'Team member #' . $member->id);
                                    $value = $member->user_id ?: $member->id;
                                @endphp
                                <option value="{{ $value }}" @selected((string) old('member_id') === (string) $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('member_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="project_id">Project</label>
                        <select id="project_id" name="project_id" class="oh-select">
                            <option value="">No project</option>
                            @foreach ($projects ?? [] as $project)
                                <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>
                                    {{ $project->project_name ?? 'Project #' . $project->id }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="contact_id">Client</label>
                        <select id="contact_id" name="contact_id" class="oh-select">
                            <option value="">No client</option>
                            @foreach ($clients ?? [] as $client)
                                @php
                                    $clientLabel = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? ''));
                                    $clientLabel = $clientLabel !== '' ? $clientLabel : ($client->email ?? 'Client #' . $client->id);
                                @endphp
                                <option value="{{ $client->id }}" @selected((string) old('contact_id') === (string) $client->id)>
                                    {{ $clientLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('contact_id')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="oh-field">
                        <label class="oh-label" for="location">Location</label>
                        <input id="location" name="location" type="text" value="{{ old('location') }}" class="oh-input"
                            placeholder="Zoom / Phone / Office" />
                        @error('location')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="oh-divider my-2"></div>

                <div class="oh-form-grid">
                    <div class="oh-field lg:col-span-2">
                        <label class="oh-label" for="description">Notes</label>
                        <textarea id="description" name="description" class="oh-textarea"
                            placeholder="Agenda, dial-in info, or prep notes.">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="oh-help text-status-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-xl p-3 ring-1 ring-border-default/60 bg-surface-card">
                        <input type="checkbox" name="all_day" value="1" class="mt-1"
                            @checked(old('all_day')) />
                        <div>
                            <div class="text-sm font-semibold text-text-base">All-day meeting</div>
                            <div class="oh-help">Use this for events without a specific time slot.</div>
                        </div>
                    </label>
                </div>

                <div class="mt-6 pt-4 border-t border-border-default/60">
                    <div class="flex flex-col sm:flex-row gap-2 sm:justify-end">
                        <a href="{{ route('tenant.calendar.index', ['tenant' => $tenantKey]) }}"
                            class="oh-btn w-full sm:w-auto justify-center">
                            Cancel
                        </a>
                        <button type="submit" class="oh-btn oh-btn--primary w-full sm:w-auto justify-center">
                            <i class="fa-solid fa-check mr-1.5"></i>
                            Schedule meeting
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection
