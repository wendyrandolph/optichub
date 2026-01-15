@extends('layouts.trades')

@section('title', 'Edit Appointment')

@section('trades-content')
    @php
        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');
        $selectedLocation = $appointment->job?->serviceLocation;
        $addressParts = array_filter([
            $selectedLocation?->address_line1,
            $selectedLocation?->address_line2,
            trim(
                ($selectedLocation?->city ?? '') .
                    ($selectedLocation?->state ? ', ' . $selectedLocation->state : '') .
                    ($selectedLocation?->postal_code ? ' ' . $selectedLocation->postal_code : ''),
            ),
        ]);
        $addressText = $addressParts ? implode(', ', $addressParts) : '';
        $mapUrl = $addressText ? 'https://maps.google.com/?q=' . urlencode($addressText) : null;
    @endphp
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3">
            <a href="{{ route('tenant.trades.schedule.show', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}"
                class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                Back to appointment
            </a>
            <div>
                <p class="text-[11px] uppercase tracking-wide text-text-subtle">Trades</p>
                <h1 class="text-2xl font-semibold text-text-base">Edit Appointment</h1>
                <p class="text-sm text-text-subtle mt-1">Update schedule details and tech assignments.</p>
            </div>
        </div>

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

        <div class="oh-card border border-border-default/60 rounded-2xl p-4 md:p-6">
            <form method="POST"
                action="{{ route('tenant.trades.schedule.update', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}"
                class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-5">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="trade_job_id">Trade job</label>
                        <select id="trade_job_id" name="trade_job_id" class="oh-select h-10">
                            <option value="">Select job</option>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" @selected((string) old('trade_job_id', $appointment->trade_job_id) === (string) $job->id)>
                                    {{ $job->summary }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-xs text-text-subtle" id="job-address-block" data-address="{{ $addressText }}"
                            data-map="{{ $mapUrl }}">
                            @if ($addressText)
                                <span id="job-address-text">{{ $addressText }}</span>
                                <a id="job-address-link" href="{{ $mapUrl }}" class="ml-2 text-text-base hover:underline"
                                    target="_blank" rel="noopener">Open in maps</a>
                            @else
                                <span id="job-address-text" class="text-amber-700">No service address on this job.</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-1.5" id="appointment-time">
                        <label class="text-sm font-medium text-text-base" for="start_at">Start</label>
                        <input id="start_at" name="start_at" type="datetime-local" class="oh-input h-10"
                            value="{{ old('start_at', $appointment->start_at?->timezone($tz)->format('Y-m-d\\TH:i')) }}" required>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="end_at">End</label>
                        <input id="end_at" name="end_at" type="datetime-local" class="oh-input h-10"
                            value="{{ old('end_at', $appointment->end_at?->timezone($tz)->format('Y-m-d\\TH:i')) }}" required>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-text-base" for="status">Status</label>
                        <select id="status" name="status" class="oh-select h-10">
                            @foreach (['scheduled' => 'Scheduled', 'in_progress' => 'In progress', 'done' => 'Done'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $appointment->status) === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5 md:col-span-2" id="appointment-assignments">
                        <label class="text-sm font-medium text-text-base" for="tech_ids">Assign techs</label>
                        <select id="tech_ids" name="tech_ids[]" class="oh-select h-10" multiple>
                            @foreach ($techs as $tech)
                                @php
                                    $techLabel =
                                        trim(($tech->first_name ?? '') . ' ' . ($tech->last_name ?? '')) ?:
                                        $tech->username ?? $tech->email;
                                    $availabilityInfo = $availability[$tech->id] ?? null;
                                    $availabilityLabel = $availabilityInfo
                                        ? ($availabilityInfo['available']
                                            ? 'Available'
                                            : 'Unavailable: ' . implode(', ', $availabilityInfo['reasons'] ?? []))
                                        : 'Availability unknown';
                                @endphp
                                <option value="{{ $tech->id }}" @selected(collect(old('tech_ids', $assignedTechIds))->contains((string) $tech->id))>
                                    {{ $techLabel }} — {{ $availabilityLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-text-subtle">Hold ⌘/Ctrl to select multiple techs.</p>
                        @if (!empty($suggestedTechs))
                            <div class="flex flex-wrap gap-2 text-xs text-text-subtle">
                                <span class="text-text-subtle">Suggested techs:</span>
                                @foreach ($suggestedTechs as $suggested)
                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700">
                                        {{ $suggested }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-sm font-medium text-text-base" for="notes">Notes</label>
                        <textarea id="notes" name="notes" class="oh-input min-h-[110px]" rows="3">{{ old('notes', $appointment->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-border-default/60">
                    <a href="{{ route('tenant.trades.schedule.show', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}"
                        class="oh-btn">Cancel</a>
                    <button class="oh-btn oh-btn--primary" type="submit">Save changes</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const jobSelect = document.getElementById('trade_job_id');
            const addressBlock = document.getElementById('job-address-block');
            const addressText = document.getElementById('job-address-text');
            const addressLink = document.getElementById('job-address-link');
            if (!jobSelect || !addressBlock || !addressText) return;

            const updateAddress = () => {
                const option = jobSelect.options[jobSelect.selectedIndex];
                const address = option?.dataset.address || '';
                const mapUrl = option?.dataset.map || '';
                if (address) {
                    addressText.textContent = address;
                    addressText.classList.remove('text-amber-700');
                    if (addressLink) {
                        addressLink.href = mapUrl;
                        addressLink.classList.remove('hidden');
                    }
                } else {
                    addressText.textContent = 'No service address on this job.';
                    addressText.classList.add('text-amber-700');
                    if (addressLink) {
                        addressLink.href = '#';
                        addressLink.classList.add('hidden');
                    }
                }
            };

            for (const opt of jobSelect.options) {
                if (opt.value === '') continue;
                const job = @json($jobs->keyBy('id'));
                const jobData = job[opt.value];
                if (jobData && jobData.service_location) {
                    const loc = jobData.service_location;
                    const parts = [
                        loc.address_line1,
                        loc.address_line2,
                        [loc.city, loc.state ? `, ${loc.state}` : '', loc.postal_code ? ` ${loc.postal_code}` : ''].join(''),
                    ].filter(Boolean);
                    const text = parts.join(', ');
                    opt.dataset.address = text;
                    opt.dataset.map = text ? `https://maps.google.com/?q=${encodeURIComponent(text)}` : '';
                } else {
                    opt.dataset.address = '';
                    opt.dataset.map = '';
                }
            }

            jobSelect.addEventListener('change', updateAddress);
            updateAddress();
        });
    </script>
@endsection
