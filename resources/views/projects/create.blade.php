@extends('layouts.app')

@section('content')
    @php
        /**
         * Expected:
         * - $clients (id, firstName, lastName, name)
         * - $users (id, username)
         * - $currentUserId (optional default owner)
         * - $projectTemplates (id, name)
         */

        $routeTenant = request()->route('tenant');
        if ($routeTenant instanceof \App\Models\Tenant) {
            $tenantId = $routeTenant->getKey();
        } elseif (is_numeric($routeTenant)) {
            $tenantId = (int) $routeTenant;
        } else {
            $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        }

        // Color palette + defaults
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
        $currentBillingModel = old('billing_model', 'fixed');
        $usesPhasesDefault = old('uses_phases', $usesPhasesDefault ?? false);
        $phaseNames = array_pad(array_slice((array) old('phases', []), 0, 5), 5, '');

        $generalError = session('general_error') ?? session('error');
        if (!$generalError && isset($errors) && method_exists($errors, 'has') && $errors->has('general')) {
            $generalError = $errors->first('general');
        }
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-3">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Projects</p>
                    <h1 class="text-2xl font-semibold text-text-base">Create project</h1>
                    <p class="text-sm text-text-subtle mt-1">Capture the basics now — you can add phases, tasks, and files
                        after saving.</p>
                </div>
                <div class="flex items-center">
                    <a href="{{ $tenantId ? route('tenant.projects.index', ['tenant' => $tenantId]) : '#' }}"
                        class="oh-btn oh-btn--ghost">
                        <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i> View all projects
                    </a>
                </div>
            </div>
        </div>

        @if ($generalError)
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">{{ $generalError }}</div>
        @endif
        @if (!$tenantId)
            <div class="oh-card border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
                Unable to resolve tenant context. Please navigate from a tenant URL like <code>/{id}/projects/create</code>.
            </div>
        @endif

        {{-- Form --}}
        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form method="POST" action="{{ route('tenant.projects.store', ['tenant' => $tenantId]) }}" novalidate
                class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    {{-- Client --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="client_id">Client</label>
                        <div class="flex items-center gap-2">
                            @php $clients = $clients ?? collect(); @endphp
                            <select name="client_id" id="client_id" required class="oh-select h-10 flex-1"
                                @disabled(!$tenantId)>
                                <option value="">Select client</option>
                                @foreach ($clients as $c)
                                    @php
                                        $clientLabel =
                                            $c->name ??
                                            (trim(($c->firstName ?? '') . ' ' . ($c->lastName ?? '')) ??
                                                trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? '')));
                                        if (!$clientLabel) {
                                            $clientLabel = 'Client #' . $c->id;
                                        }
                                    @endphp
                                    <option value="{{ $c->id }}" @selected(old('client_id') == $c->id)>{{ $clientLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if ($errors?->first('client_id'))
                            <p class="text-xs text-rose-600">{{ $errors->first('client_id') }}</p>
                        @else
                            <p class="text-xs text-text-subtle">Don’t see them? Add a minimal contact — name + email.</p>
                        @endif
                    </div>

                    {{-- Project name --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="project_name">Project name</label>
                        <input id="project_name" name="project_name" class="oh-input h-10" required
                            value="{{ old('project_name') }}" @disabled(!$tenantId)>
                        @if ($errors?->first('project_name'))
                            <p class="text-xs text-rose-600">{{ $errors->first('project_name') }}</p>
                        @endif
                    </div>

                    {{-- Owner --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="owner_id">Project owner</label>
                        @php $ownerDefault = old('owner_id', $currentUserId ?? null); @endphp
                        <select id="owner_id" name="owner_id" class="oh-select h-10" @disabled(!$tenantId)>
                            @foreach ($users ?? [] as $member)
                                @php
                                    $memberId = (int) data_get($member, 'id', 0);
                                    $userId = (int) data_get($member, 'user_id', $memberId);
                                    $fullName = trim(
                                        (data_get($member, 'firstName') ?? '') .
                                            ' ' .
                                            (data_get($member, 'lastName') ?? ''),
                                    );
                                    $fallbackFull = trim(
                                        (data_get($member, 'first_name') ?? '') .
                                            ' ' .
                                            (data_get($member, 'last_name') ?? ''),
                                    );
                                    $email = data_get($member, 'email');
                                    $username = data_get($member, 'user.username') ?? data_get($member, 'username');
                                    $label =
                                        $fullName ?: $fallbackFull ?: $username ?: $email ?: 'Member #' . $memberId;
                                @endphp
                                <option value="{{ $userId }}" @selected((string) $ownerDefault === (string) $userId)>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                        @if ($errors?->first('owner_id'))
                            <p class="text-xs text-rose-600">{{ $errors->first('owner_id') }}</p>
                        @endif
                    </div>

                    {{-- Description --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="description">Description</label>
                        <textarea id="description" name="description" class="oh-input min-h-[110px]" rows="3"
                            @disabled(!$tenantId)>{{ old('description') }}</textarea>
                        @if ($errors?->first('description'))
                            <p class="text-xs text-rose-600">{{ $errors->first('description') }}</p>
                        @endif
                    </div>



                    {{-- Dates --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="start_date">Start date</label>
                        <input id="start_date" class="oh-input h-10" type="date" name="start_date"
                            value="{{ old('start_date') }}" @disabled(!$tenantId)>
                        @if ($errors?->first('start_date'))
                            <p class="text-xs text-rose-600">{{ $errors->first('start_date') }}</p>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="end_date">End date</label>
                        <input id="end_date" class="oh-input h-10" type="date" name="end_date"
                            value="{{ old('end_date') }}" @disabled(!$tenantId)>
                        @if ($errors?->first('end_date'))
                            <p class="text-xs text-rose-600">{{ $errors->first('end_date') }}</p>
                        @else
                            <p class="text-xs text-text-subtle">Optional. We’ll prevent end date earlier than start.</p>
                        @endif
                    </div>

                    {{-- Budget --}}
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="budgeted_hours">Budgeted hours</label>
                        <input id="budgeted_hours" class="oh-input h-10" type="number" step="0.25" min="0"
                            name="budgeted_hours" value="{{ old('budgeted_hours') }}" placeholder="e.g. 40"
                            @disabled(!$tenantId)>
                        @if ($errors?->first('budgeted_hours'))
                            <p class="text-xs text-rose-600">{{ $errors->first('budgeted_hours') }}</p>
                        @endif
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="billing_model">Billing model</label>
                        <select id="billing_model" name="billing_model" class="oh-select h-10" @disabled(!$tenantId)>
                            <option value="fixed" @selected(old('billing_model', 'fixed') === 'fixed')>Fixed fee</option>
                            <option value="hourly" @selected(old('billing_model') === 'hourly')>Hourly</option>
                        </select>
                        <p class="text-xs text-text-subtle">Use fixed fee for milestone billing. Hourly keeps invoicing time-based.</p>
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
                                <input type="radio" id="{{ $cid }}" name="color"
                                    value="{{ $hex }}" @checked($chosenColor === $hex)
                                    @disabled(!$tenantId) class="sr-only peer">
                                <span
                                    class="h-8 w-8 rounded-full ring-1 ring-border-default/60 peer-checked:ring-2 peer-checked:ring-brand-primary"
                                    style="background: {{ $hex }}"></span>
                            </label>
                        @endforeach
                    </div>
                    @if ($errors?->first('color'))
                        <p class="text-xs text-rose-600">{{ $errors->first('color') }}</p>
                    @endif
                </div>
                @if ($currentBillingModel === 'fixed')
                    <div class="space-y-1.5">
                        <p class="text-sm font-semibold text-text-base">Billing &amp; profitability</p>
                        <p class="text-xs text-text-subtle">These inputs help estimate earning rate and project health.</p>
                        <div class="grid gap-3 md:grid-cols-3">
                            <label class="space-y-1">
                                <span class="text-xs uppercase tracking-[0.3em] text-text-subtle">Project fee</span>
                                <input type="number" step="0.01" min="0" name="project_fee_total"
                                    class="oh-input h-10" value="{{ old('project_fee_total') }}" placeholder="$0.00"
                                    @disabled(!$tenantId)>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs uppercase tracking-[0.3em] text-text-subtle">External costs</span>
                                <input type="number" step="0.01" min="0" name="external_costs"
                                    class="oh-input h-10" value="{{ old('external_costs', 0) }}" placeholder="$0.00"
                                    @disabled(!$tenantId)>
                            </label>
                            <label class="space-y-1">
                                <span class="text-xs uppercase tracking-[0.3em] text-text-subtle">Target rate</span>
                                <input type="number" step="0.01" min="0" name="target_hourly_rate"
                                    class="oh-input h-10" value="{{ old('target_hourly_rate') }}" placeholder="$0.00"
                                    @disabled(!$tenantId)>
                            </label>
                        </div>
                        <p class="text-xs text-text-subtle max-w-2xl">
                            Capture the fee, costs, and target rate so Renlo can keep the profitability pulse visible—no
                            spreadsheets required.
                        </p>
                    </div>
                @endif


                {{-- Use phases --}}
                <div class="space-y-1.5 md:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="uses_phases" value="1" @checked($usesPhasesDefault)
                            class="rounded border-border-default text-brand-primary">
                        <span class="text-text-base">Use phases for this project</span>
                    </label>
                    <p class="text-xs text-text-subtle">Leave unchecked for simple task-only projects.</p>
                </div>
                {{-- Template --}}
                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-text-base" for="template_id">Project Template</label>
                    <select id="template_id" name="template_id" class="oh-select h-10" @disabled(!$tenantId)>
                        <option value="">(None)</option>
                        @foreach ($projectTemplates ?? [] as $template)
                            @php $templateId = (int) data_get($template, 'id', 0); @endphp
                            <option value="{{ $templateId }}" @selected((string) old('template_id') === (string) $templateId)>
                                {{ data_get($template, 'name') }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-text-subtle">Optional: pre-load phases & tasks.</p>
                </div>
                {{-- Phases (if enabled) --}}
                <div class="md:col-span-2 space-y-2 {{ $usesPhasesDefault ? '' : 'hidden' }}" data-phase-fields>
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


        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
            <a href="{{ $tenantId ? route('tenant.projects.index', ['tenant' => $tenantId]) : '#' }}"
                class="oh-btn">Cancel</a>
            <button class="oh-btn oh-btn--primary" type="submit" @disabled(!$tenantId)>Create Project</button>
        </div>
        </form>
    </div>
    </div>

    <div id="project-data" data-palette='@json($palette)' style="display:none;"></div>
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
