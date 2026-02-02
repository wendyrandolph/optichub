@php
    $index = $index ?? 0;
    $action = $action ?? ['action_key' => 'convert_lead_to_contact', 'config' => []];
    $config = $action['config'] ?? [];
@endphp

<div class="oh-card border border-border-default/70 rounded-2xl bg-white p-4 space-y-4" data-action-row data-index="{{ $index }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex-1">
            <label class="oh-label" for="actions_{{ $index }}_action_key">Action</label>
            <select id="actions_{{ $index }}_action_key" class="oh-select h-10 w-full" data-action-key
                data-field="actions[__INDEX__][action_key]">
                <option value="convert_lead_to_contact" @selected(($action['action_key'] ?? '') === 'convert_lead_to_contact')>Convert lead to contact</option>
                <option value="create_project_from_proposal" @selected(($action['action_key'] ?? '') === 'create_project_from_proposal')>Create project from proposal</option>
                <option value="seed_tasks_from_template" @selected(($action['action_key'] ?? '') === 'seed_tasks_from_template')>Seed tasks from template</option>
                <option value="create_invoice_schedule" @selected(($action['action_key'] ?? '') === 'create_invoice_schedule')>Create invoice schedule</option>
                <option value="create_followup_task" @selected(($action['action_key'] ?? '') === 'create_followup_task')>Create follow-up task</option>
            </select>
        </div>
        <button type="button" class="oh-btn oh-btn--ghost" data-remove-action>Remove</button>
    </div>

    <div class="space-y-3">
        <div data-action-panel="convert_lead_to_contact">
            <p class="text-sm text-text-subtle">If the proposal is linked to a lead, create or link a contact.</p>
        </div>

        <div data-action-panel="create_project_from_proposal" class="hidden">
            <label class="oh-label">Project template (optional)</label>
            <select class="oh-select h-10 w-full"
                data-field="actions[__INDEX__][config][project_template_id]">
                <option value="">No template</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected(($config['project_template_id'] ?? '') == $template->id)>
                        {{ $template->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div data-action-panel="seed_tasks_from_template" class="hidden">
            <label class="oh-label">Project template</label>
            <select class="oh-select h-10 w-full"
                data-field="actions[__INDEX__][config][project_template_id]">
                <option value="">Select template</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected(($config['project_template_id'] ?? '') == $template->id)>
                        {{ $template->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div data-action-panel="create_invoice_schedule" class="hidden space-y-3">
            <div>
                <label class="oh-label">Mode</label>
                <select class="oh-select h-10 w-full"
                    data-field="actions[__INDEX__][config][mode]">
                    <option value="single" @selected(($config['mode'] ?? '') === 'single')>Single invoice</option>
                    <option value="installments" @selected(($config['mode'] ?? '') === 'installments')>Installments</option>
                </select>
            </div>
            <div>
                <label class="oh-label">Due offset (days)</label>
                <input type="number" class="oh-input h-10 w-full"
                    data-field="actions[__INDEX__][config][due_offset_days]"
                    value="{{ $config['due_offset_days'] ?? 0 }}">
            </div>
            <div class="space-y-2">
                <p class="text-xs text-text-subtle uppercase tracking-wide">Installments</p>
                <div class="grid gap-2 md:grid-cols-3">
                    <input class="oh-input h-10 w-full" placeholder="Amount"
                        data-field="actions[__INDEX__][config][installments][0][amount]"
                        value="{{ $config['installments'][0]['amount'] ?? '' }}">
                    <input class="oh-input h-10 w-full" placeholder="Percent"
                        data-field="actions[__INDEX__][config][installments][0][percent]"
                        value="{{ $config['installments'][0]['percent'] ?? '' }}">
                    <input class="oh-input h-10 w-full" placeholder="Due offset days"
                        data-field="actions[__INDEX__][config][installments][0][due_offset_days]"
                        value="{{ $config['installments'][0]['due_offset_days'] ?? '' }}">
                </div>
                <div class="grid gap-2 md:grid-cols-3">
                    <input class="oh-input h-10 w-full" placeholder="Amount"
                        data-field="actions[__INDEX__][config][installments][1][amount]"
                        value="{{ $config['installments'][1]['amount'] ?? '' }}">
                    <input class="oh-input h-10 w-full" placeholder="Percent"
                        data-field="actions[__INDEX__][config][installments][1][percent]"
                        value="{{ $config['installments'][1]['percent'] ?? '' }}">
                    <input class="oh-input h-10 w-full" placeholder="Due offset days"
                        data-field="actions[__INDEX__][config][installments][1][due_offset_days]"
                        value="{{ $config['installments'][1]['due_offset_days'] ?? '' }}">
                </div>
            </div>
        </div>

        <div data-action-panel="create_followup_task" class="hidden space-y-3">
            <div>
                <label class="oh-label">Days from now</label>
                <input type="number" class="oh-input h-10 w-full"
                    data-field="actions[__INDEX__][config][days_from_now]"
                    value="{{ $config['days_from_now'] ?? 3 }}">
            </div>
            <div>
                <label class="oh-label">Task title</label>
                <input class="oh-input h-10 w-full"
                    data-field="actions[__INDEX__][config][title]"
                    value="{{ $config['title'] ?? 'Proposal follow-up' }}">
            </div>
        </div>
    </div>
</div>
