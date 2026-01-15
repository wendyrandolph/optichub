@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="firstName">First name</label>
        <input id="firstName" name="firstName" class="oh-input h-10" value="{{ old('firstName', $client->firstName ?? '') }}"
            required>
        @error('firstName')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="lastName">Last name</label>
        <input id="lastName" name="lastName" class="oh-input h-10" value="{{ old('lastName', $client->lastName ?? '') }}"
            required>
        @error('lastName')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="email">Email</label>
        <input id="email" name="email" type="email" class="oh-input h-10"
            value="{{ old('email', $client->email ?? '') }}">
        @error('email')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5">
        <label class="text-sm font-medium text-text-base" for="phone">Phone</label>
        <input id="phone" name="phone" class="oh-input h-10"
            value="{{ old('phone', $client->phone ?? '') }}">
        @error('phone')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-text-base" for="status">Status</label>
        @php
            $statusValue = old('status', $client->status ?? 'active');
        @endphp
        <select id="status" name="status" class="oh-select h-10 w-full">
            <option value="active" @selected($statusValue === 'active')>Active</option>
            <option value="inactive" @selected($statusValue === 'inactive')>Inactive</option>
        </select>
        @error('status')
            <p class="text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
