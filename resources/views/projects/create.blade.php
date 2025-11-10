@extends('layouts.app')

@section('content')
    @php
        /**
         * Expected:
         * - $clients (id, firstName, lastName)
         * - $users (id, username) — optional
         * - $currentUserId — optional default owner
         * - $projectTemplates (id, name) — optional
         */

        // 🔒 Robust tenant ID resolution (no tenant() helper needed)
        $routeTenant = request()->route('tenant');
        if ($routeTenant instanceof \App\Models\Tenant) {
            $tenantId = $routeTenant->getKey();
        } elseif (is_numeric($routeTenant)) {
            $tenantId = (int) $routeTenant;
        } else {
            $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        }

        // Color helpers
        $palette = [
            '#1F3C66',
            '#2E5D95',
            '#5C89B5',
            '#A3C1DD',
            '#EA7D51',
            '#F28B7D',
            '#68A7A1',
            '#9EB5A6',
            '#6C7A89',
            '#333333',
            '#D0CBE6',
            '#E3C89D',
            '#B6E3C1',
            '#F3D0D7',
            '#FFD7B5',
        ];
        $selectedClientId = (int) old('client_id', 0);
        $autoColor = $palette[$selectedClientId % max(count($palette), 1)];
        $chosenColor = old('color', $autoColor);

        // General error passthrough
        $generalError = session('general_error') ?? session('error');
        if (!$generalError && isset($errors) && method_exists($errors, 'has') && $errors->has('general')) {
            $generalError = $errors->first('general');
        }
    @endphp

    <section class="form-card">
        <header class="form-card__head">
            <h2 class="form-card__title">Create New Project</h2>
            <div class="page-actions">
                @if ($tenantId)
                    <a href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}" class="btn btn--ghost">
                        <i class="fa fa-arrow-left"></i> Back to Projects
                    </a>
                @else
                    <button class="btn btn--ghost" disabled title="No tenant in context">
                        <i class="fa fa-arrow-left"></i> Back to Projects
                    </button>
                @endif
            </div>
        </header>

        @if ($generalError)
            <div class="notice notice--danger" role="alert">{{ $generalError }}</div>
        @endif

        @if (!$tenantId)
            <div class="notice notice--danger" role="alert">
                Unable to resolve tenant context. Please navigate from a tenant URL like <code>/{id}/projects/create</code>.
            </div>
        @endif

        <form method="POST" action="{{ route('tenant.projects.store', ['tenant' => $tenant->id]) }}"
            class="form-container form-grid" novalidate>
            @csrf

            {{-- A) Basics --}}
            <div class="form-group">
                <label class="label" for="client_id">Client</label>
                <div class="input-with-addon">
                    @php $clients = $clients ?? collect(); @endphp
                    <select name="client_id" id="client_id" required>
                        <option value="">Select Client</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="quickAddClient" class="btn btn--ghost" aria-haspopup="dialog"
                        aria-controls="quickClientModal" aria-expanded="false" @disabled(!$tenantId)>
                        + Quick add
                    </button>
                </div>
                @if ($errors?->first('client_id'))
                    <small class="error">{{ $errors->first('client_id') }}</small>
                @else
                    <small class="hint">Don’t see them? Add a minimal contact—name + email.</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="project_name">Project Name</label>
                <input id="project_name" name="project_name" class="input" required value="{{ old('project_name') }}"
                    @disabled(!$tenantId)>
                @if ($errors?->first('project_name'))
                    <small class="error">{{ $errors->first('project_name') }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="owner_id">Project Owner</label>
                @php $ownerDefault = old('owner_id', $currentUserId ?? null); @endphp
                <select id="owner_id" name="owner_id" class="select" @disabled(!$tenantId)>
                    @foreach ($users ?? [] as $user)
                        @php
                            $userId = (int) data_get($user, 'id', 0);
                            $username = data_get($user, 'username');
                        @endphp
                        <option value="{{ $userId }}" @selected((string) $ownerDefault === (string) $userId)>
                            {{ $username ?? 'User #' . $userId }}</option>
                    @endforeach
                </select>
                @if ($errors?->first('owner_id'))
                    <small class="error">{{ $errors->first('owner_id') }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="status">Status</label>
                @php $statuses = ['Planned','In Progress','On Hold','Completed','Cancelled']; @endphp
                <select id="status" name="status" class="select" @disabled(!$tenantId)>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', 'Planned') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @if ($errors?->first('status'))
                    <small class="error">{{ $errors->first('status') }}</small>
                @endif
            </div>

            <div class="form-group">
                <span class="label">Project Color</span>
                <div class="color-picker" role="radiogroup" aria-label="Project color">
                    @foreach ($palette as $i => $hex)
                        @php $cid = "color_{$i}"; @endphp
                        <label class="color-radio" for="{{ $cid }}">
                            <input type="radio" id="{{ $cid }}" name="color" value="{{ $hex }}"
                                @checked($chosenColor === $hex) @disabled(!$tenantId)>
                            <span class="swatch" style="--sw: {{ $hex }}"></span>
                        </label>
                    @endforeach
                </div>
                @if ($errors?->first('color'))
                    <small class="error">{{ $errors->first('color') }}</small>
                @endif
            </div>

            {{-- B) Timing & Budget --}}
            <div class="form-group">
                <label class="label" for="start_date">Start Date</label>
                <input id="start_date" class="input" type="date" name="start_date" value="{{ old('start_date') }}"
                    @disabled(!$tenantId)>
                @if ($errors?->first('start_date'))
                    <small class="error">{{ $errors->first('start_date') }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="end_date">End Date</label>
                <input id="end_date" class="input" type="date" name="end_date" value="{{ old('end_date') }}"
                    @disabled(!$tenantId)>
                @if ($errors?->first('end_date'))
                    <small class="error">{{ $errors->first('end_date') }}</small>
                @else
                    <small class="hint">Optional. We’ll prevent end date earlier than start date.</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="budgeted_hours">Budgeted Hours</label>
                <input id="budgeted_hours" class="input" type="number" step="0.25" min="0" name="budgeted_hours"
                    value="{{ old('budgeted_hours') }}" placeholder="e.g. 40" @disabled(!$tenantId)>
                @if ($errors?->first('budgeted_hours'))
                    <small class="error">{{ $errors->first('budgeted_hours') }}</small>
                @endif
            </div>

            {{-- C) Details --}}
            <div class="form-group span-2">
                <label class="label" for="description">Description</label>
                <textarea id="description" name="description" class="textarea" rows="3" @disabled(!$tenantId)>{{ old('description') }}</textarea>
                @if ($errors?->first('description'))
                    <small class="error">{{ $errors->first('description') }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="template_id">Template</label>
                <select id="template_id" name="template_id" class="select" @disabled(!$tenantId)>
                    <option value="">(None)</option>
                    @foreach ($projectTemplates ?? [] as $template)
                        @php $templateId = (int) data_get($template, 'id', 0); @endphp
                        <option value="{{ $templateId }}" @selected((string) old('template_id') === (string) $templateId)>
                            {{ data_get($template, 'name') }}
                        </option>
                    @endforeach
                </select>
                <small class="hint">Optional: pre-load phases & tasks.</small>
            </div>

            {{-- Actions --}}
            <div class="form-group centered-block">
                @if ($tenantId)
                    <a href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}"
                        class="btn btn--ghost">Cancel</a>
                    <button class="btn btn-add" type="submit">Create Project</button>
                @else
                    <button class="btn btn--ghost" disabled>Cancel</button>
                    <button class="btn btn-add" type="button" disabled>Create Project</button>
                @endif
            </div>
        </form>
    </section>

    {{-- Quick Add Client (minimal) --}}
    <div id="quickClientModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="qcTitle">
        <div class="modal-content" role="document">
            <h3 id="qcTitle">Add Client</h3>
            <form id="quickClientForm">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <label class="label" for="qc_first">First name</label>
                <input id="qc_first" class="input" name="firstName" required>
                <label class="label" for="qc_last">Last name</label>
                <input id="qc_last" class="input" name="lastName" required>
                <label class="label" for="qc_email">Email</label>
                <input id="qc_email" class="input" type="email" name="email" required>
                <div class="modal-actions">
                    <button type="button" class="btn btn--ghost" data-close>Cancel</button>
                    <button type="submit" class="btn btn-add">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="project-data" data-palette='@json($palette)' style="display:none;"></div>
@endsection
