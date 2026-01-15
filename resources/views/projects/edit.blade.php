@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
    @php
        use App\Models\Tenant;

        $rt = request()->route('tenant') ?? ($tenant ?? auth()->user()->tenant_id);
        $tenantId = $rt instanceof Tenant ? $rt->getKey() : (int) $rt;

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

        $projectId = data_get($project, 'id');
        $name = data_get($project, 'project_name', data_get($project, 'name', ''));
        $desc = data_get($project, 'description', '');
        $status = data_get($project, 'status', 'open');
        $color = data_get($project, 'color', $palette[0]);
        $budget = data_get($project, 'budgeted_hours');

        $ownerId = data_get($project, 'owner_id', data_get($project, 'user_id'));
        $companyId = data_get(
            $project,
            'client_company_id',
            data_get($project, 'company_id', data_get($project, 'organization_id')),
        );

        $startSource = data_get($project, 'start_date');
        $endSource = data_get($project, 'end_date');

        $startYmd = old('start_date');
        if ($startYmd === null) {
            $startYmd = '';
            if (!empty($startSource)) {
                try {
                    $startYmd = \Illuminate\Support\Carbon::parse($startSource)->format('Y-m-d');
                } catch (\Exception $e) {
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
                }
            }
        }

        $currentColor = old('color', $color);
        $currentStatus = old('status', $status);
        $currentOwner = old('owner_id', $ownerId);
        $currentCompany = old('client_company_id', $companyId);
        $currentBudget = old('budgeted_hours', $budget);
        $currentUsesPhases = old('uses_phases', $project->uses_phases ?? false);
        $phaseNames = old('phases', ($projectPhases ?? collect())->pluck('name')->toArray());
        $phaseNames = array_pad(array_slice($phaseNames, 0, 5), 5, '');
        $showUrl = route('tenant.projects.show', ['tenant' => $tenantId, 'project' => $projectId]);
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-3">
            <div>
                <a href="{{ route('tenant.projects.index', ['tenant' => $tenantId]) }}"
                    class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base relative">
                    <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                    <span class="relative after:absolute after:left-1/2 after:bottom-[-3px] after:h-0.5 after:w-0 after:bg-[rgb(var(--brand-accent))] after:transition-all after:duration-200 hover:after:w-full hover:after:left-0">Back to projects</span>
                </a>
            </div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Projects</p>
                    <h1 class="text-2xl font-semibold text-text-base">Edit Project</h1>
                    <p class="text-sm text-text-subtle mt-1">Update details, ownership, and branding color.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $showUrl }}" class="oh-btn">Back</a>
                    <button type="submit" form="projectEditForm" class="oh-btn oh-btn--primary">Save changes</button>
                </div>
            </div>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form id="projectEditForm" method="POST"
                action="{{ route('tenant.projects.update', ['tenant' => $tenantId, 'project' => $projectId]) }}"
                class="space-y-5" novalidate>
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    {{-- Client --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="client_id">Client</label>
                        <select name="client_id" id="client_id" class="oh-select h-10 w-full" @disabled(!$tenantId)>
                            <option value="">Select client</option>
                            @foreach ($clients ?? [] as $c)
                                @php
                                    $clientLabel = $c->name
                                        ?? trim(($c->firstName ?? '') . ' ' . ($c->lastName ?? ''))
                                        ?? trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                                    if (! $clientLabel) {
                                        $clientLabel = 'Client #' . $c->id;
                                    }
                                @endphp
                                <option value="{{ $c->id }}" @selected(old('client_id', data_get($project, 'contact_id')) == $c->id)>
                                    {{ $clientLabel }}</option>
                            @endforeach
                        </select>
                        @if ($errors?->first('client_id'))
                            <p class="text-xs text-rose-600">{{ $errors->first('client_id') }}</p>
                        @endif
                    </div>

                    {{-- Client Company --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="client_company_id">Client company</label>
                        <select name="client_company_id" id="client_company_id" class="oh-select h-10 w-full" @disabled(!$tenantId)>
                            <option value="">Select company</option>
                            @foreach ($clientCompanies ?? [] as $company)
                                @php
                                    $cid = (int) data_get($company, 'id', 0);
                                    $cname = data_get($company, 'company_name') ?? data_get($company, 'name') ?? 'Company #' . $cid;
                                @endphp
                                <option value="{{ $cid }}" @selected((string) $currentCompany === (string) $cid)>{{ $cname }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-text-subtle">Link to the account this project belongs to.</p>
                        @if ($errors?->first('client_company_id'))
                            <p class="text-xs text-rose-600">{{ $errors->first('client_company_id') }}</p>
                        @endif
                    </div>

                    {{-- Project name --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="project_name">Project name</label>
                        <input id="project_name" name="project_name" class="oh-input h-10" required
                            value="{{ old('project_name', $name) }}" @disabled(!$tenantId)>
                        @if ($errors?->first('project_name'))
                            <p class="text-xs text-rose-600">{{ $errors->first('project_name') }}</p>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="status">Status</label>
                        @php $statuses = ['open' => 'Open', 'closed' => 'Closed', 'in_progress' => 'In Progress', 'on_hold' => 'On Hold']; @endphp
                        <select id="status" name="status" class="oh-select h-10" @disabled(!$tenantId)>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($errors?->first('status'))
                            <p class="text-xs text-rose-600">{{ $errors->first('status') }}</p>
                        @endif
                    </div>

                    {{-- Owner --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="owner_id">Project owner</label>
                        <select id="owner_id" name="owner_id" class="oh-select h-10" @disabled(!$tenantId)>
                            @foreach ($users ?? [] as $member)
                                @php
                                    $memberId = (int) data_get($member, 'id', 0);
                                    $userId = (int) data_get($member, 'user_id', $memberId);
                                    $fullName = trim((data_get($member, 'firstName') ?? '') . ' ' . (data_get($member, 'lastName') ?? ''));
                                    $fallbackFull = trim((data_get($member, 'first_name') ?? '') . ' ' . (data_get($member, 'last_name') ?? ''));
                                    $email = data_get($member, 'email');
                                    $username = data_get($member, 'user.username') ?? data_get($member, 'username');
                                    $label = $fullName ?: $fallbackFull ?: $username ?: $email ?: 'Member #' . $memberId;
                                @endphp
                                <option value="{{ $userId }}" @selected((string) $currentOwner === (string) $userId)>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($errors?->first('owner_id'))
                            <p class="text-xs text-rose-600">{{ $errors->first('owner_id') }}</p>
                        @endif
                    </div>

                    {{-- Dates --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="start_date">Start date</label>
                        <input id="start_date" class="oh-input h-10" type="date" name="start_date" value="{{ $startYmd }}"
                            @disabled(!$tenantId)>
                        @if ($errors?->first('start_date'))
                            <p class="text-xs text-rose-600">{{ $errors->first('start_date') }}</p>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="end_date">End date</label>
                        <input id="end_date" class="oh-input h-10" type="date" name="end_date" value="{{ $endYmd }}"
                            @disabled(!$tenantId)>
                        @if ($errors?->first('end_date'))
                            <p class="text-xs text-rose-600">{{ $errors->first('end_date') }}</p>
                        @else
                            <p class="text-xs text-text-subtle">Optional. We’ll prevent end date earlier than start.</p>
                        @endif
                    </div>

                    {{-- Budget --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="budgeted_hours">Budgeted hours</label>
                        <input id="budgeted_hours" class="oh-input h-10" type="number" step="0.25" min="0" name="budgeted_hours"
                            value="{{ $currentBudget }}" placeholder="e.g. 40" @disabled(!$tenantId)>
                        @if ($errors?->first('budgeted_hours'))
                            <p class="text-xs text-rose-600">{{ $errors->first('budgeted_hours') }}</p>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="description">Description</label>
                        <textarea id="description" name="description" class="oh-input min-h-[110px]" rows="3" @disabled(!$tenantId)>{{ old('description', $desc) }}</textarea>
                        @if ($errors?->first('description'))
                            <p class="text-xs text-rose-600">{{ $errors->first('description') }}</p>
                        @endif
                    </div>

                    {{-- Use phases --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="uses_phases" value="1"
                                @checked($currentUsesPhases)
                                class="rounded border-border-default text-brand-primary">
                            <span class="text-text-base">Use phases for this project</span>
                        </label>
                        <p class="text-xs text-text-subtle">Leave unchecked for simple task-only projects.</p>
                    </div>

                    {{-- Phases (if enabled) --}}
                    <div class="md:col-span-2 space-y-2 {{ $currentUsesPhases ? '' : 'hidden' }}" data-phase-fields>
                        <div class="text-xs text-text-subtle">Customize up to 5 phases. Leave blank to remove.</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach ($phaseNames as $idx => $pname)
                                <label class="grid gap-1 text-sm">
                                    <span class="text-text-subtle">Phase {{ $idx + 1 }}</span>
                                    <input type="text" name="phases[]" value="{{ $pname }}"
                                        class="oh-input h-10">
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Color --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <span class="text-sm font-medium text-text-base">Project color</span>
                        <p class="text-xs text-text-subtle mb-1">Pick a color for cards and timeline markers.</p>
                        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Project color">
                            @foreach ($palette as $i => $hex)
                                @php $cid = "color_{$i}"; @endphp
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" id="{{ $cid }}" name="color" value="{{ $hex }}"
                                        @checked($currentColor === $hex) @disabled(!$tenantId)
                                        class="sr-only peer">
                                    <span class="h-8 w-8 rounded-full ring-1 ring-border-default/60 peer-checked:ring-2 peer-checked:ring-brand-primary"
                                        style="background: {{ $hex }}"></span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-text-subtle">Tip: choose a color with enough contrast for avatars and chips.</p>
                        @if ($errors?->first('color'))
                            <p class="text-xs text-rose-600">{{ $errors->first('color') }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a href="{{ $showUrl }}" class="oh-btn">Cancel</a>
                    <button class="oh-btn oh-btn--primary" type="submit" @disabled(!$tenantId)>Update Project</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('input[name="uses_phases"]');
            const phaseFields = document.querySelector('[data-phase-fields]');
            if (!toggle || !phaseFields) return;
            const sync = () => {
                phaseFields.classList.toggle('hidden', !toggle.checked);
            };
            toggle.addEventListener('change', sync);
            sync();
        });
    </script>
@endsection
