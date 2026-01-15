@php
    $tenantKey = $tenant->getRouteKey();
    $template = $template ?? null;
    $isEdit = !empty($template);

    $items = old('items');
    if ($items === null) {
        $items = $template?->items?->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->toArray() ?? [];
    }

    $checklist = old('checklist');
    if ($checklist === null) {
        $checklist = $template?->checklistItems?->map(fn ($item) => [
            'label' => $item->label,
            'is_required' => $item->is_required,
        ])->toArray() ?? [];
    }
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="name">Template name</label>
        <input id="name" name="name" class="oh-input h-10" required
            value="{{ old('name', $template->name ?? '') }}">
        @error('name')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="type">Type</label>
        <select id="type" name="type" class="oh-select h-10">
            <option value="service" @selected(old('type', $template->type ?? 'service') === 'service')>Service</option>
            <option value="project" @selected(old('type', $template->type ?? '') === 'project')>Project</option>
        </select>
        @error('type')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="default_status">Default status</label>
        <input id="default_status" name="default_status" class="oh-input h-10" required
            value="{{ old('default_status', $template->default_status ?? 'open') }}">
        @error('default_status')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="default_duration_minutes">Default duration (minutes)</label>
        <input id="default_duration_minutes" name="default_duration_minutes" class="oh-input h-10" type="number" min="0"
            value="{{ old('default_duration_minutes', $template->default_duration_minutes ?? '') }}">
        @error('default_duration_minutes')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="suggested_tech_count">Suggested tech count</label>
        <input id="suggested_tech_count" name="suggested_tech_count" class="oh-input h-10" type="number" min="0"
            value="{{ old('suggested_tech_count', $template->suggested_tech_count ?? '') }}">
        @error('suggested_tech_count')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="summary">Summary</label>
        <input id="summary" name="summary" class="oh-input h-10"
            value="{{ old('summary', $template->summary ?? '') }}">
        @error('summary')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="description">Description</label>
        <textarea id="description" name="description" class="oh-textarea" rows="3">{{ old('description', $template->description ?? '') }}</textarea>
        @error('description')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" class="rounded border-border-default"
                @checked(old('is_active', $template->is_active ?? true))>
            <span class="text-text-base">Active template</span>
        </label>
    </div>
</div>

<div class="space-y-3">
    <div class="text-sm font-medium text-text-base">Line items</div>
    <div id="template-items" class="space-y-2">
        @forelse ($items as $index => $item)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center" data-item-row>
                <input name="items[{{ $index }}][description]" class="oh-input h-9 md:col-span-2"
                    placeholder="Description" value="{{ $item['description'] ?? '' }}">
                <input name="items[{{ $index }}][quantity]" class="oh-input h-9" type="number" step="0.01" min="0"
                    placeholder="Qty" value="{{ $item['quantity'] ?? '' }}">
                <div class="flex items-center gap-2">
                    <input name="items[{{ $index }}][unit_price]" class="oh-input h-9 flex-1" type="number" step="0.01" min="0"
                        placeholder="Price" value="{{ $item['unit_price'] ?? '' }}">
                    <button type="button" class="oh-btn text-xs" data-remove-item>Remove</button>
                </div>
            </div>
        @empty
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center" data-item-row>
                <input name="items[0][description]" class="oh-input h-9 md:col-span-2" placeholder="Description">
                <input name="items[0][quantity]" class="oh-input h-9" type="number" step="0.01" min="0" placeholder="Qty">
                <div class="flex items-center gap-2">
                    <input name="items[0][unit_price]" class="oh-input h-9 flex-1" type="number" step="0.01" min="0"
                        placeholder="Price">
                    <button type="button" class="oh-btn text-xs" data-remove-item>Remove</button>
                </div>
            </div>
        @endforelse
    </div>
    <button type="button" class="oh-btn" data-add-item>Add line item</button>
</div>

<div class="space-y-3">
    <div class="text-sm font-medium text-text-base">Checklist</div>
    <div id="template-checklist" class="space-y-2">
        @forelse ($checklist as $index => $entry)
            <div class="flex flex-col md:flex-row md:items-center gap-2" data-checklist-row>
                <input name="checklist[{{ $index }}][label]" class="oh-input h-9 flex-1" placeholder="Checklist item"
                    value="{{ $entry['label'] ?? '' }}">
                <label class="inline-flex items-center gap-2 text-xs text-text-subtle">
                    <input type="checkbox" name="checklist[{{ $index }}][is_required]" value="1"
                        @checked(!empty($entry['is_required']))>
                    Required
                </label>
                <button type="button" class="oh-btn text-xs" data-remove-checklist>Remove</button>
            </div>
        @empty
            <div class="flex flex-col md:flex-row md:items-center gap-2" data-checklist-row>
                <input name="checklist[0][label]" class="oh-input h-9 flex-1" placeholder="Checklist item">
                <label class="inline-flex items-center gap-2 text-xs text-text-subtle">
                    <input type="checkbox" name="checklist[0][is_required]" value="1" checked>
                    Required
                </label>
                <button type="button" class="oh-btn text-xs" data-remove-checklist>Remove</button>
            </div>
        @endforelse
    </div>
    <button type="button" class="oh-btn" data-add-checklist>Add checklist item</button>
</div>

<template id="job-template-item-row">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center" data-item-row>
        <input name="items[__INDEX__][description]" class="oh-input h-9 md:col-span-2" placeholder="Description">
        <input name="items[__INDEX__][quantity]" class="oh-input h-9" type="number" step="0.01" min="0" placeholder="Qty">
        <div class="flex items-center gap-2">
            <input name="items[__INDEX__][unit_price]" class="oh-input h-9 flex-1" type="number" step="0.01" min="0"
                placeholder="Price">
            <button type="button" class="oh-btn text-xs" data-remove-item>Remove</button>
        </div>
    </div>
</template>

<template id="job-template-checklist-row">
    <div class="flex flex-col md:flex-row md:items-center gap-2" data-checklist-row>
        <input name="checklist[__INDEX__][label]" class="oh-input h-9 flex-1" placeholder="Checklist item">
        <label class="inline-flex items-center gap-2 text-xs text-text-subtle">
            <input type="checkbox" name="checklist[__INDEX__][is_required]" value="1" checked>
            Required
        </label>
        <button type="button" class="oh-btn text-xs" data-remove-checklist>Remove</button>
    </div>
</template>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const itemsWrap = document.getElementById('template-items');
            const checklistWrap = document.getElementById('template-checklist');
            const itemTemplate = document.getElementById('job-template-item-row')?.innerHTML;
            const checklistTemplate = document.getElementById('job-template-checklist-row')?.innerHTML;

            const renumber = (selector, prefix) => {
                document.querySelectorAll(selector).forEach((row, index) => {
                    row.querySelectorAll('input, select, textarea').forEach(input => {
                        if (!input.name) return;
                        input.name = input.name.replace(new RegExp(prefix + '\\[\\d+\\]'), `${prefix}[${index}]`);
                    });
                });
            };

            document.addEventListener('click', (event) => {
                if (event.target.matches('[data-add-item]')) {
                    event.preventDefault();
                    if (!itemTemplate || !itemsWrap) return;
                    const nextIndex = itemsWrap.querySelectorAll('[data-item-row]').length;
                    itemsWrap.insertAdjacentHTML('beforeend', itemTemplate.replace(/__INDEX__/g, String(nextIndex)));
                }
                if (event.target.matches('[data-remove-item]')) {
                    event.preventDefault();
                    const row = event.target.closest('[data-item-row]');
                    if (row) row.remove();
                    renumber('[data-item-row]', 'items');
                }

                if (event.target.matches('[data-add-checklist]')) {
                    event.preventDefault();
                    if (!checklistTemplate || !checklistWrap) return;
                    const nextIndex = checklistWrap.querySelectorAll('[data-checklist-row]').length;
                    checklistWrap.insertAdjacentHTML('beforeend', checklistTemplate.replace(/__INDEX__/g, String(nextIndex)));
                }
                if (event.target.matches('[data-remove-checklist]')) {
                    event.preventDefault();
                    const row = event.target.closest('[data-checklist-row]');
                    if (row) row.remove();
                    renumber('[data-checklist-row]', 'checklist');
                }
            });
        });
    </script>
@endpush
