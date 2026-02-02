@php
    $proposal = $proposal ?? null;
    $isEdit = (bool) $proposal;
    $tenant = $tenant ?? (request()->route('tenant') instanceof \App\Models\Tenant ? request()->route('tenant') : null);
    $isCreative = (bool) ($tenant && $tenant->workspace_type === 'creative');
    $template = $selectedTemplate ?? null;
    $recipientType = old(
        'recipient_type',
        $proposal?->recipient_type ?? ($proposal?->lead_id ? 'existing_lead' : 'new_lead')
    );
    $useExistingProject = old(
        'use_existing_project',
        $proposal?->project_id ? '1' : '0'
    );
    $goalDefaults = $proposal?->items?->where('type', 'goal')->map(fn ($item) => [
        'title' => $item->title,
        'description' => $item->description,
        'sort_order' => $item->sort_order,
    ])->values()->all()
        ?? ($template?->items?->where('type', 'goal')->map(fn ($item) => [
            'title' => $item->title,
            'description' => $item->description,
            'sort_order' => $item->sort_order,
        ])->values()->all())
        ?? [['title' => '', 'description' => '', 'sort_order' => 0]];
    $deliverableDefaults = $proposal?->items?->where('type', 'deliverable')->map(fn ($item) => [
        'title' => $item->title,
        'description' => $item->description,
        'sort_order' => $item->sort_order,
    ])->values()->all()
        ?? ($template?->items?->where('type', 'deliverable')->map(fn ($item) => [
            'title' => $item->title,
            'description' => $item->description,
            'sort_order' => $item->sort_order,
        ])->values()->all())
        ?? [['title' => '', 'description' => '', 'sort_order' => 0]];
    $goals = old('goals', $goalDefaults);
    $deliverables = old('deliverables', $deliverableDefaults);
    $proposalSchedule = isset($paymentSchedule)
        ? $paymentSchedule->map(function ($item) {
            return [
                'label' => $item->label,
                'amount' => $item->amount,
                'due_trigger' => $item->due_trigger,
                'note' => $item->note,
            ];
        })->toArray()
        : ($proposal?->paymentScheduleItems?->map(function ($item) {
            return [
                'label' => $item->label,
                'amount' => $item->amount,
                'due_trigger' => $item->due_trigger,
                'note' => $item->note,
            ];
        })->toArray() ?? []);
    $paymentSchedule = old('payment_schedule', $proposalSchedule ?: ($template?->payment_schedule ?? []));
    $timeline = old('timeline', $proposal?->timeline ?? $template?->timeline ?? []);
    $maintenancePlan = old('maintenance_plan', $proposal?->maintenance_plan ?? []);
@endphp

<div class="grid gap-6 lg:grid-cols-12">
    <div class="lg:col-span-8 space-y-8">
        <div class="space-y-6">
            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-text-base">Basics</h2>
                        <p class="text-xs text-text-subtle mt-1">Set the foundation for this proposal.</p>
                    </div>
                    <span class="oh-pill">{{ $proposal?->statusLabel() ?? 'Draft' }}</span>
                </div>
                <div class="mt-3 grid gap-4 md:grid-cols-2">
                    @if (!$isEdit)
                        <div class="md:col-span-2">
                            <label class="oh-label" for="template_id">Template (optional)</label>
                            <select id="template_id" name="template_id" class="oh-select h-10 w-full"
                                data-template-select
                                data-template-base="{{ route('tenant.proposals.create', ['tenant' => $tenantId]) }}">
                                <option value="">Start from scratch</option>
                                @foreach (($templates ?? []) as $tpl)
                                    <option value="{{ $tpl->id }}" @selected(old('template_id', $template?->id) == $tpl->id)>
                                        {{ $tpl->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-text-subtle mt-1">Selecting a template pre-fills sections you can edit.</p>
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <label class="oh-label" for="title">Proposal title</label>
                        <input id="title" name="title" class="oh-input h-10 w-full" value="{{ old('title', $proposal->title ?? $template?->title ?? '') }}" required>
                        @error('title')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="oh-label" for="use_existing_project">
                            <input id="use_existing_project" type="checkbox" name="use_existing_project" value="1"
                                class="mr-2" @checked($useExistingProject === '1') data-project-toggle>
                            Use existing project
                        </label>
                    </div>
                    <div class="md:col-span-2" data-project-panel="existing">
                        <label class="oh-label" for="project_id">Project</label>
                        <select id="project_id" name="project_id" class="oh-select h-10 w-full">
                            <option value="">Select a project</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected(old('project_id', $proposal->project_id ?? null) == $project->id)>
                                    {{ $project->project_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2" data-project-panel="new">
                        <label class="oh-label" for="project_name">Project name</label>
                        <input id="project_name" name="project_name" class="oh-input h-10 w-full"
                            value="{{ old('project_name') }}" placeholder="New project name">
                        @error('project_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    @if (!$isCreative)
                        <div>
                            <label class="oh-label" for="client_id">Client</label>
                            <select id="client_id" name="client_id" class="oh-select h-10 w-full" required>
                                <option value="">Select a client</option>
                                @foreach ($clients as $client)
                                    @php
                                        $clientName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: ($client->email ?? 'Client');
                                    @endphp
                                    <option value="{{ $client->id }}" @selected(old('client_id', $proposal->client_id ?? null) == $client->id)>
                                        {{ $clientName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    @if ($isCreative)
                        <div class="md:col-span-2">
                            <label class="oh-label">Recipient type</label>
                            <div class="mt-2 flex flex-wrap gap-3" data-recipient-toggle>
                                <label class="flex items-center gap-2 text-sm text-text-base">
                                    <input type="radio" name="recipient_type" value="new_lead"
                                        @checked($recipientType === 'new_lead')>
                                    New lead
                                </label>
                                <label class="flex items-center gap-2 text-sm text-text-base">
                                    <input type="radio" name="recipient_type" value="existing_lead"
                                        @checked($recipientType === 'existing_lead')>
                                    Existing lead
                                </label>
                                <label class="flex items-center gap-2 text-sm text-text-base">
                                    <input type="radio" name="recipient_type" value="existing_contact"
                                        @checked($recipientType === 'existing_contact')>
                                    Existing contact
                                </label>
                            </div>
                        </div>
                        <div class="md:col-span-2 hidden" data-recipient-panel="existing_lead">
                            <label class="oh-label" for="lead_id">Lead</label>
                            <select id="lead_id" name="lead_id" class="oh-select h-10 w-full">
                                <option value="">Select a lead</option>
                                @foreach ($leads as $lead)
                                    <option value="{{ $lead->id }}" @selected(old('lead_id', $proposal?->lead_id ?? null) == $lead->id)>
                                        {{ $lead->name ?? $lead->email ?? 'Lead' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lead_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2 hidden" data-recipient-panel="existing_contact">
                            <label class="oh-label" for="contact_id">Contact</label>
                            <select id="contact_id" name="contact_id" class="oh-select h-10 w-full">
                                <option value="">Select a contact</option>
                                @foreach ($clients as $client)
                                    @php
                                        $clientName = trim(($client->firstName ?? '') . ' ' . ($client->lastName ?? '')) ?: ($client->email ?? 'Contact');
                                    @endphp
                                    <option value="{{ $client->id }}" @selected(old('contact_id', $proposal->contact_id ?? $proposal->client_id ?? null) == $client->id)>
                                        {{ $clientName }}
                                    </option>
                                @endforeach
                            </select>
                            @error('contact_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2 grid gap-3 md:grid-cols-2 hidden" data-recipient-panel="new_lead">
                            <div>
                                <label class="oh-label" for="lead_name">Lead name</label>
                                <input id="lead_name" name="lead_name" class="oh-input h-10 w-full"
                                    value="{{ old('lead_name', $proposal?->lead?->name ?? '') }}">
                            </div>
                            <div>
                                <label class="oh-label" for="lead_email">Lead email</label>
                                <input id="lead_email" name="lead_email" class="oh-input h-10 w-full"
                                    value="{{ old('lead_email', $proposal?->lead?->email ?? '') }}">
                            </div>
                            <div>
                                <label class="oh-label" for="lead_phone">Lead phone</label>
                                <input id="lead_phone" name="lead_phone" class="oh-input h-10 w-full"
                                    value="{{ old('lead_phone', $proposal?->lead?->phone ?? '') }}">
                            </div>
                            <div>
                                <label class="oh-label" for="lead_company">Company</label>
                                <input id="lead_company" name="lead_company" class="oh-input h-10 w-full"
                                    value="{{ old('lead_company', $proposal?->lead?->company?->company_name ?? '') }}">
                            </div>
                            @error('lead_name')
                                <p class="text-xs text-rose-600 mt-1 md:col-span-2">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div class="md:col-span-2">
                        <input type="hidden" name="status" value="{{ old('status', $proposal->status ?? 'draft') }}">
                    </div>
                </div>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-3">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Purpose / overview</h2>
                    <p class="text-xs text-text-subtle mt-1">Summarize the project intent in a few sentences.</p>
                </div>
                <textarea name="summary" class="oh-textarea w-full mt-3" rows="3" data-normalize-paste>{{ old('summary', $proposal->summary ?? $template?->intro_text ?? '') }}</textarea>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-text-base">Goals</h2>
                        <p class="text-xs text-text-subtle mt-1">Capture the outcomes you want to achieve.</p>
                    </div>
                    <button type="button" class="oh-btn oh-btn--ghost" data-add="goal">Add goal</button>
                </div>
                <div class="mt-3 space-y-3" data-list="goal">
                    <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-[1fr,120px] items-start" data-template="goal" data-index="__INDEX__" style="display:none;">
                        <div class="space-y-2">
                            <input data-field="title" class="oh-input h-10 w-full" data-normalize-paste placeholder="Goal title" disabled>
                            <textarea data-field="description" rows="2" class="oh-textarea w-full" data-normalize-paste placeholder="Optional details" disabled></textarea>
                            <input type="hidden" data-field="sort_order" value="0" disabled>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="up">Move up</button>
                            <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="down">Move down</button>
                            <button type="button" class="oh-btn w-full" data-remove>Remove</button>
                        </div>
                    </div>
                    @foreach ($goals as $idx => $goal)
                        <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-[1fr,120px] items-start" data-row data-index="{{ $idx }}">
                            <div class="space-y-2">
                                <input name="goals[{{ $idx }}][title]" data-field="title" class="oh-input h-10 w-full" data-normalize-paste value="{{ $goal['title'] ?? '' }}" placeholder="Goal title">
                                <textarea name="goals[{{ $idx }}][description]" data-field="description" rows="2" class="oh-textarea w-full" data-normalize-paste placeholder="Optional details">{{ $goal['description'] ?? '' }}</textarea>
                                <input type="hidden" name="goals[{{ $idx }}][sort_order]" data-field="sort_order" value="{{ $goal['sort_order'] ?? $idx }}">
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="up">Move up</button>
                                <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="down">Move down</button>
                                <button type="button" class="oh-btn w-full" data-remove>Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-text-base">Objectives & deliverables</h2>
                        <p class="text-xs text-text-subtle mt-1">List the outputs the client can expect.</p>
                    </div>
                    <button type="button" class="oh-btn oh-btn--ghost" data-add="deliverable">Add deliverable</button>
                </div>
                <div class="mt-3 space-y-3" data-list="deliverable">
                    <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-[1fr,120px] items-start" data-template="deliverable" data-index="__INDEX__" style="display:none;">
                        <div class="space-y-2">
                            <input data-field="title" class="oh-input h-10 w-full" data-normalize-paste placeholder="Deliverable title" disabled>
                            <textarea data-field="description" rows="2" class="oh-textarea w-full" data-normalize-paste placeholder="Optional details" disabled></textarea>
                            <input type="hidden" data-field="sort_order" value="0" disabled>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="up">Move up</button>
                            <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="down">Move down</button>
                            <button type="button" class="oh-btn w-full" data-remove>Remove</button>
                        </div>
                    </div>
                    @foreach ($deliverables as $idx => $deliverable)
                        <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-[1fr,120px] items-start" data-row data-index="{{ $idx }}">
                            <div class="space-y-2">
                                <input name="deliverables[{{ $idx }}][title]" data-field="title" class="oh-input h-10 w-full" data-normalize-paste value="{{ $deliverable['title'] ?? '' }}" placeholder="Deliverable title">
                                <textarea name="deliverables[{{ $idx }}][description]" data-field="description" rows="2" class="oh-textarea w-full" data-normalize-paste placeholder="Optional details">{{ $deliverable['description'] ?? '' }}</textarea>
                                <input type="hidden" name="deliverables[{{ $idx }}][sort_order]" data-field="sort_order" value="{{ $deliverable['sort_order'] ?? $idx }}">
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="up">Move up</button>
                                <button type="button" class="oh-btn oh-btn--ghost w-full" data-move="down">Move down</button>
                                <button type="button" class="oh-btn w-full" data-remove>Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-4">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Project investment & payment terms</h2>
                    <p class="text-xs text-text-subtle mt-1">Break payments into milestones to keep work moving.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="oh-label" for="total_investment">Total investment</label>
                        <input id="total_investment" name="total_investment" type="number" step="0.01"
                            class="oh-input h-10 w-full" value="{{ old('total_investment', $proposal->total_investment ?? $template?->default_total ?? '') }}">
                    </div>
                </div>
                <div class="space-y-4" data-list="payment">
                    <p class="text-xs text-text-subtle">Each installment includes a label and amount, with an optional trigger and note.</p>
                    <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 space-y-3" data-template="payment" data-index="__INDEX__" style="display:none;">
                        <div class="grid gap-3 md:grid-cols-[1fr,180px] items-center">
                            <div class="space-y-1">
                                <label class="text-[11px] uppercase tracking-wide text-text-subtle">Label</label>
                                <input data-field="label" class="oh-input h-10 w-full" placeholder="Initial payment" disabled>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[11px] uppercase tracking-wide text-text-subtle">Amount</label>
                                <input data-field="amount" type="number" step="0.01" class="oh-input h-10 w-full" placeholder="0.00" disabled>
                            </div>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="space-y-1">
                                <label class="text-[11px] uppercase tracking-wide text-text-subtle">Trigger</label>
                                <select data-field="due_trigger" class="oh-select h-10 w-full" disabled>
                                    @foreach (['on_acceptance' => 'On acceptance', 'after_design_approval' => 'After design approval', 'before_launch' => 'Before launch', 'custom' => 'Custom'] as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[11px] uppercase tracking-wide text-text-subtle">Note</label>
                                <input data-field="note" class="oh-input h-10 w-full" placeholder="Optional note" disabled>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" class="oh-btn oh-btn--ghost" data-add-inline style="display:none;">Add installment</button>
                            <button type="button" class="oh-btn" data-remove>Remove</button>
                        </div>
                    </div>
                    @forelse ($paymentSchedule as $row)
                        <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 space-y-3" data-row data-index="{{ $loop->index }}">
                            <div class="grid gap-3 md:grid-cols-[1fr,180px] items-center">
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Label</label>
                                    <input name="payment_schedule[{{ $loop->index }}][label]" data-field="label" class="oh-input h-10 w-full" value="{{ $row['label'] ?? '' }}">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Amount</label>
                                    <input name="payment_schedule[{{ $loop->index }}][amount]" data-field="amount" type="number" step="0.01" class="oh-input h-10 w-full" value="{{ $row['amount'] ?? '' }}">
                                </div>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Trigger</label>
                                    <select name="payment_schedule[{{ $loop->index }}][due_trigger]" data-field="due_trigger" class="oh-select h-10 w-full">
                                        @foreach (['on_acceptance' => 'On acceptance', 'after_design_approval' => 'After design approval', 'before_launch' => 'Before launch', 'custom' => 'Custom'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($row['due_trigger'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Note</label>
                                    <input name="payment_schedule[{{ $loop->index }}][note]" data-field="note" class="oh-input h-10 w-full" value="{{ $row['note'] ?? '' }}">
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" class="oh-btn oh-btn--ghost" data-add-inline style="display:none;">Add installment</button>
                                <button type="button" class="oh-btn" data-remove>Remove</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 space-y-3" data-row data-index="0">
                            <div class="grid gap-3 md:grid-cols-[1fr,180px] items-center">
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Label</label>
                                    <input name="payment_schedule[0][label]" data-field="label" class="oh-input h-10 w-full" placeholder="Initial payment">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Amount</label>
                                    <input name="payment_schedule[0][amount]" data-field="amount" type="number" step="0.01" class="oh-input h-10 w-full" placeholder="0.00">
                                </div>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Trigger</label>
                                    <select name="payment_schedule[0][due_trigger]" data-field="due_trigger" class="oh-select h-10 w-full">
                                        @foreach (['on_acceptance' => 'On acceptance', 'after_design_approval' => 'After design approval', 'before_launch' => 'Before launch', 'custom' => 'Custom'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Note</label>
                                    <input name="payment_schedule[0][note]" data-field="note" class="oh-input h-10 w-full" placeholder="Optional note">
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" class="oh-btn oh-btn--ghost" data-add-inline>Add installment</button>
                                <button type="button" class="oh-btn" data-remove>Remove</button>
                            </div>
                        </div>
                    @endforelse
                    <p class="text-xs text-text-subtle">Third-party tools may be billed separately.</p>
                </div>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-muted/50 p-6 shadow-sm space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-text-base">Maintenance & hosting</h2>
                        <p class="text-xs text-text-subtle mt-1">Optional recurring support plan.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-text-subtle">
                        <input type="checkbox" name="maintenance_plan[enabled]" value="1"
                            @checked(old('maintenance_plan.enabled', $maintenancePlan['enabled'] ?? false))>
                        Enable plan
                    </label>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="oh-label" for="maintenance_plan_monthly">Monthly amount</label>
                        <input id="maintenance_plan_monthly" name="maintenance_plan[monthly_amount]" type="number" step="0.01"
                            class="oh-input h-10 w-full" value="{{ old('maintenance_plan.monthly_amount', $maintenancePlan['monthly_amount'] ?? '') }}">
                    </div>
                    <div>
                        <label class="oh-label" for="maintenance_plan_cancellation">Cancellation terms</label>
                        <input id="maintenance_plan_cancellation" name="maintenance_plan[cancellation_terms]" class="oh-input h-10 w-full"
                            value="{{ old('maintenance_plan.cancellation_terms', $maintenancePlan['cancellation_terms'] ?? 'Cancel anytime with 14 days notice') }}">
                    </div>
                </div>
                <div>
                    <label class="oh-label">Includes</label>
                    <div class="mt-2 space-y-2" data-list="maintenance">
                        <div class="flex items-center gap-2" data-template="maintenance" style="display:none;">
                            <input name="maintenance_plan[includes][]" class="oh-input h-10 w-full" placeholder="Maintenance item">
                            <button type="button" class="oh-btn" data-remove>Remove</button>
                        </div>
                        @foreach (old('maintenance_plan.includes', $maintenancePlan['includes'] ?? ['Backups', 'Updates', 'Monitoring']) as $item)
                            <div class="flex items-center gap-2" data-row>
                                <input name="maintenance_plan[includes][]" class="oh-input h-10 w-full" value="{{ $item }}">
                                <button type="button" class="oh-btn" data-remove>Remove</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="oh-btn oh-btn--ghost mt-2" data-add="maintenance">Add item</button>
                </div>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-3">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Payment policy</h2>
                    <p class="text-xs text-text-subtle mt-1">Set expectations around payment timing.</p>
                </div>
                <textarea name="payment_policy" class="oh-textarea w-full mt-2" rows="3" data-normalize-paste>{{ old('payment_policy', $proposal->payment_policy ?? $template?->payment_policy_text ?? 'Invoices due within 14 days; late payments may affect the timeline.') }}</textarea>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold text-text-base">Timeline</h2>
                        <p class="text-xs text-text-subtle mt-1">Outline phases and expectations.</p>
                    </div>
                    <button type="button" class="oh-btn oh-btn--ghost" data-add="timeline">Add phase</button>
                </div>
                <div class="mt-3 space-y-3" data-list="timeline">
                    <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-3 items-center" data-template="timeline" style="display:none;">
                        <input name="timeline[][phase]" class="oh-input h-10 w-full" placeholder="Phase">
                        <input name="timeline[][duration]" class="oh-input h-10 w-full" placeholder="Duration">
                        <input name="timeline[][description]" class="oh-input h-10 w-full" placeholder="Details">
                    </div>
                    @forelse ($timeline as $row)
                        <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-3 items-center" data-row>
                            <input name="timeline[][phase]" class="oh-input h-10 w-full" value="{{ $row['phase'] ?? '' }}" placeholder="Phase">
                            <input name="timeline[][duration]" class="oh-input h-10 w-full" value="{{ $row['duration'] ?? '' }}" placeholder="Duration">
                            <input name="timeline[][description]" class="oh-input h-10 w-full" value="{{ $row['description'] ?? '' }}" placeholder="Details">
                        </div>
                    @empty
                        <div class="rounded-xl border border-border-default/60 bg-surface-muted/40 p-4 grid gap-3 md:grid-cols-3 items-center" data-row>
                            <input name="timeline[][phase]" class="oh-input h-10 w-full" placeholder="Discovery">
                            <input name="timeline[][duration]" class="oh-input h-10 w-full" placeholder="1 week">
                            <input name="timeline[][description]" class="oh-input h-10 w-full" placeholder="Planning + kickoff">
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-6 shadow-sm space-y-3">
                <div>
                    <h2 class="text-base font-semibold text-text-base">Next steps</h2>
                    <p class="text-xs text-text-subtle mt-1">Explain how the client should respond.</p>
                </div>
                <textarea name="next_steps" class="oh-textarea w-full mt-2" rows="3" data-normalize-paste>{{ old('next_steps', $proposal->next_steps ?? $template?->next_steps_text ?? 'Reply to approve, and we will schedule kickoff.') }}</textarea>
            </div>

            <div class="oh-card border border-border-default/50 rounded-2xl bg-surface-muted/50 p-5 shadow-sm flex items-center justify-end gap-2">
                <a href="{{ ($isEdit ?? false) && ($proposal?->id ?? null)
                        ? route('tenant.proposals.show', ['tenant' => $tenantId, 'proposal' => $proposal->id])
                        : route('tenant.proposals.index', ['tenant' => $tenantId]) }}"
                    class="oh-btn">
                    Cancel
                </a>
                <button type="submit" class="oh-btn oh-btn--primary">{{ $isEdit ? 'Save changes' : 'Save proposal' }}</button>
            </div>
        </div>
    </div>

    {{-- Summary card removed in edit mode for clarity --}}
</div>

@if ($isCreative)
    @push('scripts')
        <script>
            (function () {
                const form = document.querySelector('[data-proposal-form]');
                if (!form) return;
                const panels = form.querySelectorAll('[data-recipient-panel]');
                const toggle = form.querySelector('[data-recipient-toggle]');
                const updatePanels = () => {
                    const checked = form.querySelector('input[name="recipient_type"]:checked');
                    const value = checked ? checked.value : 'new_lead';
                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.getAttribute('data-recipient-panel') !== value);
                    });
                };
                if (toggle) {
                    toggle.addEventListener('change', updatePanels);
                }
                updatePanels();
            })();
        </script>
    @endpush
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-proposal-form]');
    if (!form) return;
    const toggle = form.querySelector('[data-project-toggle]');
    const panels = form.querySelectorAll('[data-project-panel]');
    const updateProjectPanels = () => {
        const useExisting = !!toggle?.checked;
        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-project-panel') !== (useExisting ? 'existing' : 'new'));
        });
    };
    if (toggle) {
        toggle.addEventListener('change', updateProjectPanels);
        updateProjectPanels();
    }
});

document.addEventListener('click', function (event) {
    const inlineAdd = event.target.closest('[data-add-inline]');
    const add = event.target.closest('[data-add]');
    if (!inlineAdd && !add) return;
    const type = inlineAdd ? 'payment' : add.getAttribute('data-add');
    const list = document.querySelector(`[data-list="${type}"]`);
    if (!list) return;
    const template = list.querySelector(`[data-template="${type}"]`);
    if (!template) return;
    const clone = template.cloneNode(true);
    clone.style.display = '';
    clone.removeAttribute('data-template');
    clone.setAttribute('data-row', '');
    clone.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.disabled = false;
    });
    clone.querySelectorAll('textarea').forEach(textarea => {
        textarea.value = '';
        textarea.disabled = false;
    });
    clone.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
        select.disabled = false;
    });
    list.appendChild(clone);
    if (type === 'payment') {
        updatePaymentIndexes(list);
        updatePaymentAddButtons(list);
    }
    if (type === 'goal') {
        updateIndexedList(list, 'goals');
    }
    if (type === 'deliverable') {
        updateIndexedList(list, 'deliverables');
    }
    updateSortOrders(list);
});

document.addEventListener('click', function (event) {
    const remove = event.target.closest('[data-remove]');
    if (!remove) return;
    const row = remove.closest('[data-row]') || remove.closest('div');
    if (!row) return;
    const list = row.parentElement;
    const rows = list ? list.querySelectorAll('[data-row]') : [];
    if (rows.length > 1) row.remove();
    if (list) {
        if (list.getAttribute('data-list') === 'payment') {
            updatePaymentIndexes(list);
            updatePaymentAddButtons(list);
        }
        if (list.getAttribute('data-list') === 'goal') {
            updateIndexedList(list, 'goals');
        }
        if (list.getAttribute('data-list') === 'deliverable') {
            updateIndexedList(list, 'deliverables');
        }
        updateSortOrders(list);
    }
});

document.addEventListener('click', function (event) {
    const move = event.target.closest('[data-move]');
    if (!move) return;
    const row = move.closest('[data-row]');
    const list = row?.parentElement;
    if (!row || !list) return;
    const direction = move.getAttribute('data-move');
    if (direction === 'up' && row.previousElementSibling) {
        list.insertBefore(row, row.previousElementSibling);
    } else if (direction === 'down' && row.nextElementSibling) {
        list.insertBefore(row.nextElementSibling, row);
    }
    updateSortOrders(list);
});

document.addEventListener('paste', function (event) {
    const target = event.target;
    if (!target || !target.matches('[data-normalize-paste]')) {
        return;
    }
    const text = (event.clipboardData || window.clipboardData)?.getData('text');
    if (!text) {
        return;
    }
    event.preventDefault();
    const normalized = text
        .replace(/\u00A0/g, ' ')
        .replace(/\s*\n\s*/g, ' ')
        .replace(/\s{2,}/g, ' ');
    const start = target.selectionStart ?? target.value.length;
    const end = target.selectionEnd ?? target.value.length;
    target.value = target.value.slice(0, start) + normalized + target.value.slice(end);
    const cursor = start + normalized.length;
    target.selectionStart = cursor;
    target.selectionEnd = cursor;
});

document.addEventListener('change', function (event) {
    const select = event.target.closest('[data-template-select]');
    if (!select) return;
    const base = select.getAttribute('data-template-base');
    if (!base) return;
    const value = select.value;
    const url = value ? `${base}?template_id=${encodeURIComponent(value)}` : base;
    window.location.href = url;
});

function updateSortOrders(list) {
    const rows = list.querySelectorAll('[data-row]');
    rows.forEach((row, index) => {
        const sortInput = row.querySelector('input[name$="[sort_order]"]');
        if (sortInput) sortInput.value = index;
    });
}

function updateIndexedList(list, base) {
    const rows = list.querySelectorAll('[data-row]');
    rows.forEach((row, index) => {
        row.setAttribute('data-index', index);
        row.querySelectorAll('[data-field]').forEach((field) => {
            const name = `${base}[${index}][${field.getAttribute('data-field')}]`;
            field.setAttribute('name', name);
        });
    });
}

function updatePaymentIndexes(list) {
    const rows = list.querySelectorAll('[data-row]');
    rows.forEach((row, index) => {
        row.setAttribute('data-index', index);
        row.querySelectorAll('[data-field]').forEach((field) => {
            const name = `payment_schedule[${index}][${field.getAttribute('data-field')}]`;
            field.setAttribute('name', name);
        });
    });
}

function updatePaymentAddButtons(list) {
    const rows = list.querySelectorAll('[data-row]');
    rows.forEach((row, index) => {
        const addBtn = row.querySelector('[data-add-inline]');
        if (!addBtn) return;
        addBtn.style.display = index === rows.length - 1 ? 'inline-flex' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const paymentList = document.querySelector('[data-list="payment"]');
    if (paymentList) {
        updatePaymentAddButtons(paymentList);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const paymentList = document.querySelector('[data-list="payment"]');
    if (paymentList) {
        updatePaymentIndexes(paymentList);
    }
    const goalsList = document.querySelector('[data-list="goal"]');
    if (goalsList) {
        updateIndexedList(goalsList, 'goals');
    }
    const deliverablesList = document.querySelector('[data-list="deliverable"]');
    if (deliverablesList) {
        updateIndexedList(deliverablesList, 'deliverables');
    }
});
</script>
@endpush
