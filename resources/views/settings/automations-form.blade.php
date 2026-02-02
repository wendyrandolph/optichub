@php
    $rule = $rule ?? null;
    $isEdit = (bool) $rule;
    $actions = old('actions', $rule?->actionItems?->map(function ($action) {
        return [
            'action_key' => $action->action_key,
            'config' => $action->config_json ?? [],
        ];
    })->toArray() ?? []);
    if (empty($actions)) {
        $actions = [['action_key' => 'convert_lead_to_contact', 'config' => []]];
    }
@endphp

<div class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6 space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="oh-label" for="name">Rule name</label>
            <input id="name" name="name" class="oh-input h-10 w-full" value="{{ old('name', $rule->name ?? '') }}" required>
            @error('name')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="oh-label" for="trigger_key">Trigger</label>
            <select id="trigger_key" name="trigger_key" class="oh-select h-10 w-full" required>
                <option value="proposal_sent" @selected(old('trigger_key', $rule->trigger_key ?? '') === 'proposal_sent')>Proposal sent</option>
                <option value="proposal_approved" @selected(old('trigger_key', $rule->trigger_key ?? '') === 'proposal_approved')>Proposal approved</option>
            </select>
            @error('trigger_key')
                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-2">
            <label class="flex items-center gap-2 text-sm text-text-base">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $rule->enabled ?? true))>
                Enabled
            </label>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Actions</p>
                <p class="text-sm text-text-subtle mt-1">Run actions in order when the trigger fires.</p>
            </div>
            <button type="button" class="oh-btn oh-btn--ghost" data-add-action>Add action</button>
        </div>

        <div class="space-y-4" data-action-list>
            <div class="hidden" data-action-template>
                @include('settings.automations-row', [
                    'index' => '__INDEX__',
                    'action' => ['action_key' => 'convert_lead_to_contact', 'config' => []],
                    'templates' => $templates,
                ])
            </div>
            @foreach ($actions as $index => $action)
                @include('settings.automations-row', [
                    'index' => $index,
                    'action' => $action,
                    'templates' => $templates,
                ])
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const list = document.querySelector('[data-action-list]');
            const template = document.querySelector('[data-action-template]');
            const addBtn = document.querySelector('[data-add-action]');
            if (!list || !template || !addBtn) return;

            const updateIndexes = () => {
                list.querySelectorAll('[data-action-row]').forEach((row, idx) => {
                    row.dataset.index = idx;
                    row.querySelectorAll('[data-field]').forEach((field) => {
                        const name = field.dataset.field.replace('__INDEX__', idx);
                        field.name = name;
                    });
                });
            };

            const bindRow = (row) => {
                const removeBtn = row.querySelector('[data-remove-action]');
                if (removeBtn) {
                    removeBtn.addEventListener('click', () => {
                        row.remove();
                        updateIndexes();
                    });
                }
                const select = row.querySelector('[data-action-key]');
                if (select) {
                    const updatePanels = () => {
                        const value = select.value;
                        row.querySelectorAll('[data-action-panel]').forEach((panel) => {
                            panel.classList.toggle('hidden', panel.dataset.actionPanel !== value);
                        });
                    };
                    select.addEventListener('change', updatePanels);
                    updatePanels();
                }
            };

            addBtn.addEventListener('click', () => {
                const clone = template.firstElementChild.cloneNode(true);
                clone.classList.remove('hidden');
                list.appendChild(clone);
                bindRow(clone);
                updateIndexes();
            });

            list.querySelectorAll('[data-action-row]').forEach((row) => bindRow(row));
            updateIndexes();
        })();
    </script>
@endpush
