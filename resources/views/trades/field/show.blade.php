@extends('layouts.trades')

@section('title', 'Appointment')

@section('trades-content')
    @php
        $isTech = auth()->user()?->isTech();
        $job = $appointment->job;
        $clientName = trim(($job?->client?->firstName ?? '') . ' ' . ($job?->client?->lastName ?? '')) ?: 'Client';
        $companyName = $job?->company?->company_name ?? null;
        $location = $job?->serviceLocation;
        $isTimerForThis = $openTimer && (int) $openTimer->appointment_id === (int) $appointment->id;
        $tenantKey = $tenant->getRouteKey();
        $tz = $tenant->timezone ?? config('app.timezone');
        $isToday = $appointment->start_at?->timezone($tz)?->isSameDay(now($tz)) ?? false;
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('tenant.trades.field.today', ['tenant' => $tenantKey]) }}"
                    class="inline-flex items-center text-[11px] font-semibold uppercase tracking-wide text-text-subtle hover:text-text-base">
                    <i class="fa-solid fa-arrow-left mr-2 text-[10px]"></i>
                    Back to today
                </a>
                <h1 class="text-2xl font-semibold text-text-base mt-2">{{ $job?->summary ?? 'Job' }}</h1>
                <p class="text-sm text-text-subtle mt-1">
                    {{ $clientName }}@if ($companyName)
                        · {{ $companyName }}
                    @endif
                </p>
            </div>



            {{-- show clock-in / clock-out / start / stop buttons --}}

            <div class="flex flex-wrap items-center gap-2">
                <span class="oh-pill oh-pill--muted text-[11px]">{{ ucfirst($appointment->status) }}</span>
                @if ($isTech)
                    <span class="oh-pill oh-pill--muted text-[11px]">
                        {{ $appointment->start_at?->timezone($tz)->format('M j, g:i A') }} -
                        {{ $appointment->end_at?->timezone($tz)->format('g:i A') }}
                    </span>
                @endif
            </div>
        </div>

        @if (session('success_message'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success_message') }}
            </div>
        @endif
        @if (session('error_message'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error_message') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!$openShift)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Clock in before starting a job.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="oh-card p-5 space-y-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-semibold text-text-base">Job controls</div>
                            <div class="text-xs text-text-subtle mt-1">
                                @if ($isTimerForThis)
                                    Timer started at {{ $openTimer->started_at?->timezone($tz)->format('g:i A') }}.
                                @elseif ($openTimer)
                                    Finish your current job before starting this one.
                                @elseif (!$isToday)
                                    This job is scheduled for {{ $appointment->start_at?->timezone($tz)->format('M j') }}.
                                @else
                                    Ready when you are.
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($isTimerForThis)
                                <form method="POST"
                                    action="{{ route('tenant.trades.field.stop', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                    @csrf
                                    <button class="oh-btn oh-btn--danger" type="submit">Stop job</button>
                                </form>
                            @else
                                <form method="POST"
                                    action="{{ route('tenant.trades.field.start', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                    @csrf
                                    <button class="oh-btn oh-btn--primary" type="submit" @disabled(!$openShift || $openTimer || !$isToday)>
                                        Start job
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="text-sm font-semibold text-text-base">Checklist</div>
                    <div
                        class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                        No items yet.
                    </div>
                </div>

                <div class="oh-card p-5 space-y-4">
                    <div class="text-sm font-semibold text-text-base">Photos &amp; notes</div>

                    <div class="space-y-2">
                        <div class="text-xs uppercase tracking-wide text-text-subtle">Notes</div>
                        @if ($appointment->fieldNotes->isEmpty())
                            <div
                                class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                                No notes yet.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($appointment->fieldNotes as $note)
                                    @php
                                        $author =
                                            $note->user?->name ??
                                            trim(($note->user?->first_name ?? '') . ' ' . ($note->user?->last_name ?? '')) ?:
                                            $note->user?->username ??
                                            'Tech';
                                        $noteAt = $note->created_at?->timezone($tz)->format('M j, g:i A');
                                    @endphp
                                    <div class="rounded-lg border border-border-default px-4 py-3">
                                        <div class="text-sm text-text-base">{{ $note->note }}</div>
                                        <div class="text-xs text-text-subtle mt-2">{{ $author }} · {{ $noteAt }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST"
                            action="{{ route('tenant.trades.field.notes.store', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}"
                            class="space-y-2">
                            @csrf
                            <label class="text-xs text-text-subtle">Add note</label>
                            <textarea name="note" class="oh-input min-h-[90px]" rows="3"
                                placeholder="Add a quick update for the office...">{{ old('note') }}</textarea>
                            <div class="flex justify-end">
                                <button class="oh-btn oh-btn--primary" type="submit">Save note</button>
                            </div>
                        </form>
                    </div>

                    <div class="space-y-2">
                        <div class="text-xs uppercase tracking-wide text-text-subtle">Photos</div>
                        @if ($appointment->fieldPhotos->isEmpty())
                            <div
                                class="rounded-lg border border-border-default bg-surface-muted/60 px-4 py-3 text-sm text-text-subtle">
                                No photos yet.
                            </div>
                        @else
                            <ul class="space-y-2 text-sm">
                                @foreach ($appointment->fieldPhotos as $photo)
                                    @php
                                        $photoAuthor =
                                            $photo->user?->name ??
                                            trim(
                                                ($photo->user?->first_name ?? '') . ' ' . ($photo->user?->last_name ?? ''),
                                            ) ?:
                                            $photo->user?->username ??
                                            'Tech';
                                        $photoAt = $photo->created_at?->timezone($tz)->format('M j, g:i A');
                                    @endphp
                                    <li class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-text-base truncate">{{ $photo->original_name }}</span>
                                        <span class="text-xs text-text-subtle">{{ $photoAuthor }} · {{ $photoAt }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <form method="POST" enctype="multipart/form-data"
                            action="{{ route('tenant.trades.field.photos.store', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}"
                            class="space-y-2">
                            @csrf
                            <label class="text-xs text-text-subtle">Upload photos</label>
                            <div class="flex items-center gap-3">
                                <input id="fieldPhotos" type="file" name="photos[]" multiple class="sr-only">
                                <label for="fieldPhotos"
                                    class="inline-flex items-center rounded-md border border-[rgb(var(--border))] bg-[rgb(var(--surface))] px-3 py-2 text-sm font-medium text-[rgb(var(--text))] shadow-sm hover:bg-[rgb(var(--surface-muted))] focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgba(var(--ui-primary),0.35)] cursor-pointer">
                                    Choose files
                                </label>
                                <span id="fieldPhotosFilename" class="text-sm text-text-subtle">No files chosen</span>
                            </div>
                            <div class="flex justify-end">
                                <button class="oh-btn" type="submit">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="oh-card p-5 space-y-3">
                    <div class="text-sm font-semibold text-text-base">Service location</div>
                    @if ($location)
                        <div class="text-sm text-text-base">{{ $location->label ?? 'Service location' }}</div>
                        <div class="text-xs text-text-subtle">
                            {{ $location->address_line1 }}
                            @if ($location->address_line2)
                                , {{ $location->address_line2 }}
                            @endif
                            <br>
                            {{ $location->city }}, {{ $location->state }} {{ $location->postal_code }}
                        </div>
                        @if ($location->access_notes)
                            <div class="text-xs text-text-subtle mt-2">Notes: {{ $location->access_notes }}</div>
                        @endif
                    @else
                        <div class="text-sm text-text-subtle">No service location linked.</div>
                    @endif
                </div>

                <div class="oh-card p-5 space-y-3">
                    <div class="text-sm font-semibold text-text-base">Team presence</div>
                    <div class="space-y-2">
                        @foreach ($appointment->assignments as $slotAssignment)
                            @php
                                $name =
                                    $slotAssignment->user?->name ??
                                    (trim(
                                        ($slotAssignment->user?->first_name ?? '') .
                                            ' ' .
                                            ($slotAssignment->user?->last_name ?? ''),
                                    ) ??
                                        ($slotAssignment->user?->username ?? 'Tech'));
                                $presenceLabel = str_replace(
                                    '_',
                                    ' ',
                                    ucfirst($slotAssignment->presence_status ?? 'assigned'),
                                );
                            @endphp
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-text-base">{{ $name }}</span>
                                <span class="oh-pill oh-pill--muted text-[11px]">{{ $presenceLabel }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($isTech && $assignment)
                        <div class="mt-3 rounded-lg border border-border-default bg-surface-muted/60 px-3 py-2">
                            <div class="text-xs text-text-subtle">Update your presence</div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm text-text-base">
                                    {{ str_replace('_', ' ', ucfirst($assignment->presence_status ?? 'assigned')) }}
                                </span>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($isTimerForThis)
                                        <form method="POST"
                                            action="{{ route('tenant.trades.field.stop', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                            @csrf
                                            <button class="oh-btn oh-btn--danger" type="submit">Stop job</button>
                                        </form>
                                    @else
                                        <form method="POST"
                                            action="{{ route('tenant.trades.field.start', ['tenant' => $tenantKey, 'appointment' => $appointment->id]) }}">
                                            @csrf
                                            <button class="oh-btn oh-btn--primary" type="submit"
                                                @disabled(!$openShift || $openTimer || !$isToday)>
                                                Start job
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const input = document.getElementById('fieldPhotos');
                const label = document.getElementById('fieldPhotosFilename');
                if (!input || !label) return;

                input.addEventListener('change', () => {
                    const files = input.files || [];
                    if (!files.length) {
                        label.textContent = 'No files chosen';
                        return;
                    }
                    if (files.length === 1) {
                        label.textContent = files[0].name;
                        return;
                    }
                    label.textContent = `${files.length} files selected`;
                });
            });
        </script>
    @endpush
@endsection
