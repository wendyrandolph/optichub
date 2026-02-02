@extends('layouts.app')

@section('title', isset($isEdit) && $isEdit ? 'Edit proposal template' : 'Create proposal template')

@section('content')
    @php
        $tenantId = $tenant?->id ?? (int) (request()->route('tenant') ?? auth()->user()?->tenant_id);
        $isEdit = $isEdit ?? false;
        $template = $template ?? null;
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Sales</p>
                <h1 class="text-2xl font-semibold text-text-base">{{ $isEdit ? 'Edit proposal template' : 'Create proposal template' }}</h1>
                <p class="text-sm text-text-subtle mt-1">{{ $isEdit ? 'Update a reusable structure for future proposals.' : 'Create a reusable structure for future proposals.' }}</p>
            </div>
            <a href="{{ route('tenant.proposal-templates.index', ['tenant' => $tenantId]) }}" class="oh-btn">Back to templates</a>
        </div>

        <form method="POST" action="{{ $isEdit ? route('tenant.proposal-templates.update', ['tenant' => $tenantId, 'proposal_template' => $template->id]) : route('tenant.proposal-templates.store', ['tenant' => $tenantId]) }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif
            @php
                $goalDefaults = $template?->items?->where('type', 'goal')->map(fn ($item) => [
                    'title' => $item->title,
                    'description' => $item->description,
                    'sort_order' => $item->sort_order,
                ])->values()->all() ?? [['title' => '', 'description' => '', 'sort_order' => 0]];
                $deliverableDefaults = $template?->items?->where('type', 'deliverable')->map(fn ($item) => [
                    'title' => $item->title,
                    'description' => $item->description,
                    'sort_order' => $item->sort_order,
                ])->values()->all() ?? [['title' => '', 'description' => '', 'sort_order' => 0]];
                $goals = old('goals', $goalDefaults);
                $deliverables = old('deliverables', $deliverableDefaults);
                $paymentSchedule = old('payment_schedule', $template?->payment_schedule ?? []);
                $timeline = old('timeline', $template?->timeline ?? []);
            @endphp

            <div class="oh-card border border-border-default/70 rounded-2xl p-6 space-y-6">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Basics</p>
                    <div class="mt-3 grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="oh-label" for="name">Template name</label>
                            <input id="name" name="name" class="oh-input h-10 w-full" value="{{ old('name', $template->name ?? '') }}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="oh-label" for="title">Proposal title</label>
                            <input id="title" name="title" class="oh-input h-10 w-full" value="{{ old('title', $template->title ?? '') }}">
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Purpose / overview</p>
                    <textarea name="intro_text" class="oh-textarea w-full mt-2" rows="3" data-normalize-paste>{{ old('intro_text', $template->intro_text ?? '') }}</textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Goals</p>
                        <button type="button" class="oh-btn oh-btn--ghost" data-add="goal">Add goal</button>
                    </div>
                    <div class="mt-3 space-y-2" data-list="goal">
                        <div class="grid gap-2 md:grid-cols-[1fr,140px] items-start" data-template="goal" data-index="__INDEX__" style="display:none;">
                            <div class="space-y-2">
                                <input data-field="title" class="oh-input h-10 w-full" placeholder="Goal title" disabled>
                                <textarea data-field="description" rows="2" class="oh-textarea w-full" placeholder="Optional details" disabled></textarea>
                                <input type="hidden" data-field="sort_order" value="0" disabled>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button type="button" class="oh-btn" data-move="up">Move up</button>
                                <button type="button" class="oh-btn" data-move="down">Move down</button>
                                <button type="button" class="oh-btn" data-remove>Remove</button>
                            </div>
                        </div>
                        @foreach ($goals as $idx => $goal)
                            <div class="grid gap-2 md:grid-cols-[1fr,140px] items-start" data-row data-index="{{ $idx }}">
                                <div class="space-y-2">
                                    <input name="goals[{{ $idx }}][title]" data-field="title" class="oh-input h-10 w-full" value="{{ $goal['title'] ?? '' }}" placeholder="Goal title">
                                    <textarea name="goals[{{ $idx }}][description]" data-field="description" rows="2" class="oh-textarea w-full" placeholder="Optional details">{{ $goal['description'] ?? '' }}</textarea>
                                    <input type="hidden" name="goals[{{ $idx }}][sort_order]" data-field="sort_order" value="{{ $goal['sort_order'] ?? $idx }}">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button type="button" class="oh-btn" data-move="up">Move up</button>
                                    <button type="button" class="oh-btn" data-move="down">Move down</button>
                                    <button type="button" class="oh-btn" data-remove>Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Deliverables</p>
                        <button type="button" class="oh-btn oh-btn--ghost" data-add="deliverable">Add deliverable</button>
                    </div>
                    <div class="mt-3 space-y-2" data-list="deliverable">
                        <div class="grid gap-2 md:grid-cols-[1fr,140px] items-start" data-template="deliverable" data-index="__INDEX__" style="display:none;">
                            <div class="space-y-2">
                                <input data-field="title" class="oh-input h-10 w-full" placeholder="Deliverable title" disabled>
                                <textarea data-field="description" rows="2" class="oh-textarea w-full" placeholder="Optional details" disabled></textarea>
                                <input type="hidden" data-field="sort_order" value="0" disabled>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button type="button" class="oh-btn" data-move="up">Move up</button>
                                <button type="button" class="oh-btn" data-move="down">Move down</button>
                                <button type="button" class="oh-btn" data-remove>Remove</button>
                            </div>
                        </div>
                        @foreach ($deliverables as $idx => $deliverable)
                            <div class="grid gap-2 md:grid-cols-[1fr,140px] items-start" data-row data-index="{{ $idx }}">
                                <div class="space-y-2">
                                    <input name="deliverables[{{ $idx }}][title]" data-field="title" class="oh-input h-10 w-full" value="{{ $deliverable['title'] ?? '' }}" placeholder="Deliverable title">
                                    <textarea name="deliverables[{{ $idx }}][description]" data-field="description" rows="2" class="oh-textarea w-full" placeholder="Optional details">{{ $deliverable['description'] ?? '' }}</textarea>
                                    <input type="hidden" name="deliverables[{{ $idx }}][sort_order]" data-field="sort_order" value="{{ $deliverable['sort_order'] ?? $idx }}">
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button type="button" class="oh-btn" data-move="up">Move up</button>
                                    <button type="button" class="oh-btn" data-move="down">Move down</button>
                                    <button type="button" class="oh-btn" data-remove>Remove</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-text-subtle">Investment & payment schedule</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="oh-label" for="default_total">Default total</label>
                            <input id="default_total" name="default_total" type="number" step="0.01" class="oh-input h-10 w-full"
                                value="{{ old('default_total', $template->default_total ?? '') }}">
                        </div>
                    </div>
                    <div class="space-y-3" data-list="payment">
                        <p class="text-xs text-text-subtle">Each installment includes a label and amount, with an optional trigger and note.</p>
                        <div class="rounded-xl border border-border-default p-4 space-y-3" data-template="payment" data-index="__INDEX__" style="display:none;">
                            <div class="grid gap-3 md:grid-cols-[1fr,180px,auto] items-center">
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Label</label>
                                    <input data-field="label" class="oh-input h-10 w-full" placeholder="Initial payment" disabled>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[11px] uppercase tracking-wide text-text-subtle">Amount</label>
                                    <input data-field="amount" type="number" step="0.01" class="oh-input h-10 w-full" placeholder="0.00" disabled>
                                </div>
                                <button type="button" class="oh-btn" data-remove>Remove</button>
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
                        </div>
                        @forelse ($paymentSchedule as $row)
                            <div class="rounded-xl border border-border-default p-4 space-y-3" data-row data-index="{{ $loop->index }}">
                                <div class="grid gap-3 md:grid-cols-[1fr,180px,auto] items-center">
                                    <div class="space-y-1">
                                        <label class="text-[11px] uppercase tracking-wide text-text-subtle">Label</label>
                                        <input name="payment_schedule[{{ $loop->index }}][label]" data-field="label" class="oh-input h-10 w-full" value="{{ $row['label'] ?? '' }}">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] uppercase tracking-wide text-text-subtle">Amount</label>
                                        <input name="payment_schedule[{{ $loop->index }}][amount]" data-field="amount" type="number" step="0.01" class="oh-input h-10 w-full" value="{{ $row['amount'] ?? '' }}">
                                    </div>
                                    <button type="button" class="oh-btn" data-remove>Remove</button>
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
                            </div>
                        @empty
                            <div class="rounded-xl border border-border-default p-4 space-y-3" data-row data-index="0">
                                <div class="grid gap-3 md:grid-cols-[1fr,180px,auto] items-center">
                                    <div class="space-y-1">
                                        <label class="text-[11px] uppercase tracking-wide text-text-subtle">Label</label>
                                        <input name="payment_schedule[0][label]" data-field="label" class="oh-input h-10 w-full" placeholder="Initial payment">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[11px] uppercase tracking-wide text-text-subtle">Amount</label>
                                        <input name="payment_schedule[0][amount]" data-field="amount" type="number" step="0.01" class="oh-input h-10 w-full" placeholder="0.00">
                                    </div>
                                    <button type="button" class="oh-btn" data-remove>Remove</button>
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
                            </div>
                        @endforelse
                        <button type="button" class="oh-btn oh-btn--ghost" data-add="payment">Add installment</button>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Timeline</p>
                    <textarea name="timeline_text" class="oh-textarea w-full mt-2" rows="3" data-normalize-paste>{{ old('timeline_text', $template->timeline_text ?? '') }}</textarea>
                    <div class="mt-3 space-y-2" data-list="timeline">
                        <div class="grid gap-3 md:grid-cols-3 items-center" data-template="timeline" style="display:none;">
                            <input name="timeline[][phase]" class="oh-input h-10 w-full" placeholder="Phase">
                            <input name="timeline[][duration]" class="oh-input h-10 w-full" placeholder="Duration">
                            <input name="timeline[][description]" class="oh-input h-10 w-full" placeholder="Details">
                        </div>
                        @forelse ($timeline as $row)
                            <div class="grid gap-3 md:grid-cols-3 items-center" data-row>
                                <input name="timeline[][phase]" class="oh-input h-10 w-full" value="{{ $row['phase'] ?? '' }}" placeholder="Phase">
                                <input name="timeline[][duration]" class="oh-input h-10 w-full" value="{{ $row['duration'] ?? '' }}" placeholder="Duration">
                                <input name="timeline[][description]" class="oh-input h-10 w-full" value="{{ $row['description'] ?? '' }}" placeholder="Details">
                            </div>
                        @empty
                            <div class="grid gap-3 md:grid-cols-3 items-center" data-row>
                                <input name="timeline[][phase]" class="oh-input h-10 w-full" placeholder="Discovery">
                                <input name="timeline[][duration]" class="oh-input h-10 w-full" placeholder="1 week">
                                <input name="timeline[][description]" class="oh-input h-10 w-full" placeholder="Planning + kickoff">
                            </div>
                        @endforelse
                    </div>
                    <button type="button" class="oh-btn oh-btn--ghost mt-2" data-add="timeline">Add phase</button>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Next steps</p>
                    <textarea name="next_steps_text" class="oh-textarea w-full mt-2" rows="3" data-normalize-paste>{{ old('next_steps_text', $template->next_steps_text ?? '') }}</textarea>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-wide text-text-subtle">Payment policy</p>
                    <textarea name="payment_policy_text" class="oh-textarea w-full mt-2" rows="3" data-normalize-paste>{{ old('payment_policy_text', $template->payment_policy_text ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4">
                <a href="{{ route('tenant.proposal-templates.index', ['tenant' => $tenantId]) }}" class="oh-btn">Cancel</a>
                <button type="submit" class="oh-btn oh-btn--primary">{{ $isEdit ? 'Save changes' : 'Create template' }}</button>
            </div>
        </form>
    </div>

@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const add = event.target.closest('[data-add]');
    if (!add) return;
    const type = add.getAttribute('data-add');
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
    </div>
@endsection
