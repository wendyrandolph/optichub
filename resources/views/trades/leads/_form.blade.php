@php
    $lead = $lead ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="name">Lead name</label>
        <input id="name" name="name" class="oh-input h-10" value="{{ old('name', $lead->name ?? '') }}">
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="source">Source</label>
        <select id="source" name="source" class="oh-select h-10">
            <option value="">Select source</option>
            @foreach ($sourceOptions ?? [] as $opt)
                <option value="{{ $opt }}" @selected(old('source', $lead->source ?? '') === $opt)>
                    {{ ucfirst($opt) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="first_name">First name</label>
        <input id="first_name" name="first_name" class="oh-input h-10"
            value="{{ old('first_name', $lead->first_name ?? '') }}">
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="last_name">Last name</label>
        <input id="last_name" name="last_name" class="oh-input h-10"
            value="{{ old('last_name', $lead->last_name ?? '') }}">
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="email">Email</label>
        <input id="email" name="email" type="email" class="oh-input h-10"
            value="{{ old('email', $lead->email ?? '') }}">
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="phone">Phone</label>
        <input id="phone" name="phone" class="oh-input h-10"
            value="{{ old('phone', $lead->phone ?? '') }}">
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="status">Status</label>
        <select id="status" name="status" class="oh-select h-10">
            @foreach ($statusOptions ?? [] as $opt)
                <option value="{{ $opt }}" @selected(old('status', $lead->status ?? 'new') === $opt)>
                    {{ ucfirst($opt) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="preferred_time">Preferred time</label>
        <input id="preferred_time" name="preferred_time" class="oh-input h-10"
            value="{{ old('preferred_time', $lead->preferred_time ?? '') }}"
            placeholder="Weekdays after 3pm">
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="service_address">Service address</label>
        <textarea id="service_address" name="service_address" class="oh-textarea min-h-[80px]">{{ old('service_address', $lead->service_address ?? '') }}</textarea>
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="description">Description</label>
        <textarea id="description" name="description" class="oh-textarea min-h-[100px]">{{ old('description', $lead->description ?? '') }}</textarea>
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="notes">Notes</label>
        <textarea id="notes" name="notes" class="oh-textarea min-h-[100px]">{{ old('notes', $lead->notes ?? '') }}</textarea>
    </div>
</div>
