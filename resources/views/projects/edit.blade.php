@extends('layouts.app')

@section('content')
    @php
        $tenantId = $tenant->id ?? (auth()->user()?->tenant_id ?? tenant('id'));
        $palette = ['#1F3C66', '#2E5D95', '#5C89B5', '#A3C1DD', '#EA7D51', '#F28B7D', '#68A7A1', '#9EB5A6', '#6C7A89', '#333333', '#D0CBE6', '#E3C89D', '#B6E3C1', '#F3D0D7', '#FFD7B5'];
        $projectId = data_get($project, 'id');
        $projectName = data_get($project, 'project_name', data_get($project, 'name', ''));
        $projectDescription = data_get($project, 'description', '');
        $projectStatus = data_get($project, 'status', 'open');
        $projectColor = data_get($project, 'color', $palette[0]);
        $projectBudget = data_get($project, 'budgeted_hours');
        $projectOwnerId = data_get($project, 'owner_id', data_get($project, 'user_id'));
        $projectClientId = data_get($project, 'project_client_id', data_get($project, 'client_user_id'));

        $startSource = data_get($project, 'start_date');
        $endSource = data_get($project, 'end_date');

        $startYmd = old('start_date');
        if ($startYmd === null) {
            $startYmd = '';
            if (!empty($startSource)) {
                try {
                    $startYmd = \Illuminate\Support\Carbon::parse($startSource)->format('Y-m-d');
                } catch (\Exception $e) {
                    $startYmd = '';
                }
            }
        }

        $endYmd = old('end_date');
        if ($endYmd === null) {
            $endYmd = '';
            if (!empty($endSource)) {
                try {
                    $endYmd = \Illuminate\Support\Carbon::parse($endSource)->format('Y-m-d');
                } catch (\Exception $e) {
                    $endYmd = '';
                }
            }
        }

        $currentColor = old('color', $projectColor);
        $currentStatus = old('status', $projectStatus);
        $currentOwner = old('owner_id', $projectOwnerId);
        $currentClient = old('client_id', $projectClientId);
        $currentBudget = old('budgeted_hours', $projectBudget);

        $generalError = session('general_error') ?? session('error');
        if (!$generalError && isset($errors) && method_exists($errors, 'has') && $errors->has('general')) {
            $generalError = $errors->first('general');
        }

        $errorLookup = static function (string $field) use ($errors) {
            if (!isset($errors)) {
                return null;
            }
            if (method_exists($errors, 'first')) {
                $message = $errors->first($field);
                if ($message) {
                    return $message;
                }
            }
            if (is_array($errors) && isset($errors[$field])) {
                return $errors[$field];
            }
            return null;
        };
    @endphp

    <section class="form-card">
        <header class="form-card__head">
            <h2 class="form-card__title">Edit Project</h2>
            <div class="page-actions">
                <a href="{{ route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $projectId]) }}" class="btn btn--ghost">
                    <i class="fa fa-arrow-left"></i> Back to Project
                </a>
            </div>
        </header>

        @if ($generalError)
            <div class="notice notice--danger" role="alert">
                {{ $generalError }}
            </div>
        @endif

        <form method="POST"
            action="{{ route('tenant.projects.update', ['tenant' => $tenantId, 'project' => $projectId]) }}"
            class="form-container form-grid" novalidate>
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="label" for="client_id">Client</label>
                <div class="input-with-addon">
                    <select class="select" id="client_id" name="client_id" required>
                        <option value="">Select Client</option>
                        @foreach (($clients ?? []) as $client)
                            @php
                                $clientId = (int) data_get($client, 'id', 0);
                                $clientName = trim(
                                    (string) data_get($client, 'firstName', '') . ' ' . (string) data_get($client, 'lastName', '')
                                );
                            @endphp
                            <option value="{{ $clientId }}" @selected((string) $currentClient === (string) $clientId)>
                                {{ $clientName !== '' ? $clientName : 'Client #' . $clientId }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($clientError = $errorLookup('client_id'))
                    <small class="error">{{ $clientError }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="project_name">Project Name</label>
                <input id="project_name" type="text" name="project_name" class="input"
                    value="{{ old('project_name', $projectName) }}" required>
                @if ($nameError = $errorLookup('project_name'))
                    <small class="error">{{ $nameError }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="description">Description</label>
                <textarea id="description" name="description" rows="3" class="textarea"
                    placeholder="What is this project about?">{{ old('description', $projectDescription) }}</textarea>
                @if ($descriptionError = $errorLookup('description'))
                    <small class="error">{{ $descriptionError }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="status">Status</label>
                @php
                    $statuses = ['open' => 'Open', 'closed' => 'Closed'];
                @endphp
                <select id="status" name="status" class="select">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($statusError = $errorLookup('status'))
                    <small class="error">{{ $statusError }}</small>
                @endif
            </div>

            <div class="form-group">
                <span class="label">Project Color</span>
                <div class="color-picker" role="radiogroup" aria-label="Project color">
                    @foreach ($palette as $index => $hex)
                        @php($colorInputId = "color_{$index}")
                        <label class="color-radio" for="{{ $colorInputId }}">
                            <input type="radio" id="{{ $colorInputId }}" name="color" value="{{ $hex }}"
                                @checked($currentColor === $hex)>
                            <span class="swatch" style="--sw: {{ $hex }}"></span>
                        </label>
                    @endforeach
                </div>
                @if ($colorError = $errorLookup('color'))
                    <small class="error">{{ $colorError }}</small>
                @endif
            </div>

            <div class="row">
                <div class="form-group">
                    <label class="label" for="start_date">Start Date</label>
                    <input class="input" id="start_date" type="date" name="start_date"
                        value="{{ $startYmd ?? '' }}">
                    @if ($startError = $errorLookup('start_date'))
                        <small class="error">{{ $startError }}</small>
                    @endif
                </div>
                <div class="form-group">
                    <label class="label" for="end_date">End Date</label>
                    <input class="input" id="end_date" type="date" name="end_date"
                        value="{{ $endYmd ?? '' }}">
                    @if ($endError = $errorLookup('end_date'))
                        <small class="error">{{ $endError }}</small>
                    @endif
                </div>
            </div>

            <div class="form-group">
                <label class="label" for="budgeted_hours">Budgeted Hours</label>
                <input class="input" id="budgeted_hours" type="number" step="0.25" min="0"
                    name="budgeted_hours" value="{{ $currentBudget }}">
                @if ($budgetError = $errorLookup('budgeted_hours'))
                    <small class="error">{{ $budgetError }}</small>
                @endif
            </div>

            <div class="form-group">
                <label class="label" for="owner_id">Project Owner</label>
                <select id="owner_id" name="owner_id" class="select">
                    @foreach (($users ?? []) as $user)
                        @php
                            $userId = (int) data_get($user, 'id', 0);
                            $username = data_get($user, 'username');
                        @endphp
                        <option value="{{ $userId }}" @selected((string) $currentOwner === (string) $userId)>
                            {{ $username ?? ('User #' . $userId) }}
                        </option>
                    @endforeach
                </select>
                @if ($ownerError = $errorLookup('owner_id'))
                    <small class="error">{{ $ownerError }}</small>
                @endif
            </div>

            <div class="form-actions">
                <a class="btn btn--secondary"
                    href="{{ route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $projectId]) }}">
                    Cancel
                </a>
                <button class="btn btn--primary" type="submit">Update Project</button>
            </div>
        </form>
    </section>
@endsection
