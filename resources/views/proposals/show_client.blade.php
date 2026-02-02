@extends('layouts.public-proposal')

@section('title', $proposal->title ?? 'Proposal')

@section('content')
    @php
        $clientName = $proposal->recipientName();
        $goalItems = $proposal->items?->where('type', 'goal') ?? collect();
        $deliverableItems = $proposal->items?->where('type', 'deliverable') ?? collect();
        $timeline = $proposal->timeline ?? [];
        $maintenancePlan = $proposal->maintenance_plan ?? [];
        $paymentSchedule = $paymentSchedule ?? $proposal->paymentScheduleItems ?? collect();
        $logoPath = $proposal->tenant?->logo_path ? asset('storage/' . $proposal->tenant->logo_path) : asset('images/renlo-logo.svg');
        $statusLabel = ucfirst(str_replace('_', ' ', (string) $proposal->status));
        $signed = in_array(strtolower((string) $proposal->status), ['approved', 'accepted'], true);
        $signature = $signature ?? null;
    @endphp

    <div class="min-h-screen bg-[rgb(var(--ui-bg))] text-[rgb(var(--ui-text))]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
            <header class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 md:p-8 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.3em] text-text-subtle">Proposal</p>
                        <h1 class="text-2xl md:text-3xl font-semibold text-text-base">{{ $proposal->title }}</h1>
                        <p class="text-sm text-text-base mt-1">Prepared for {{ $clientName }}</p>
                        <p class="text-sm text-text-base">Prepared by {{ $proposal->tenant?->name ?? 'Workspace' }}</p>
                        <div class="mt-2">
                            <span class="oh-pill">{{ $statusLabel }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <img src="{{ $logoPath }}" alt="{{ $proposal->tenant?->name ?? 'Workspace' }} logo"
                            class="h-16 w-auto max-w-[300px] rounded-2xl bg-[rgb(var(--ui-surface))] p-2 object-contain">
                    </div>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-12">
                <main class="lg:col-span-8 space-y-6">
                    @if (!empty($isPreview) && strtolower((string) $proposal->status) === 'draft')
                        <div class="oh-card border border-border-default/70 rounded-2xl bg-[rgba(var(--ui-primary),0.08)] p-4 text-xs text-text-subtle">
                            Internal preview — this draft is visible to logged-in team members only.
                        </div>
                    @endif

            @if ($proposal->summary)
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                            <h2 class="text-base font-semibold text-text-base">Purpose / overview</h2>
                            <p class="text-sm text-text-base mt-2">{{ $proposal->summary }}</p>
                        </section>
                    @endif

            @if ($goalItems->isNotEmpty())
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                            <h2 class="text-base font-semibold text-text-base">Goals</h2>
                            <ul class="mt-3 space-y-2 text-sm">
                                @foreach ($goalItems as $goal)
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-check text-[12px] text-text-subtle mt-1"></i>
                                        <div>
                                            <div class="font-medium text-text-base">{{ $goal->title }}</div>
                                            @if ($goal->description)
                                                <div class="text-xs text-text-base mt-1">{{ $goal->description }}</div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

            @if ($deliverableItems->isNotEmpty())
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                            <h2 class="text-base font-semibold text-text-base">Objectives & deliverables</h2>
                            <ul class="mt-3 space-y-2 text-sm">
                                @foreach ($deliverableItems as $deliverable)
                                    <li class="flex items-start gap-2">
                                        <i class="fa-solid fa-check text-[12px] text-text-subtle mt-1"></i>
                                        <div>
                                            <div class="font-medium text-text-base">{{ $deliverable->title }}</div>
                                            @if ($deliverable->description)
                                                <div class="text-xs text-text-base mt-1">{{ $deliverable->description }}</div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

            @if (!empty($timeline))
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                            <h2 class="text-base font-semibold text-text-base">Timeline</h2>
                            <div class="mt-3 space-y-3 text-sm">
                                @foreach ($timeline as $phase)
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-text-base">{{ $phase['phase'] ?? 'Phase' }}</div>
                                            <div class="text-xs text-text-base">{{ $phase['description'] ?? '' }}</div>
                                        </div>
                                        <div class="text-xs text-text-base">{{ $phase['duration'] ?? '' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

            @if ($proposal->next_steps)
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                            <h2 class="text-base font-semibold text-text-base">Next steps</h2>
                            <p class="text-sm text-text-base mt-2">{{ $proposal->next_steps }}</p>
                        </section>
                    @endif
                </main>

                <aside class="lg:col-span-4 space-y-6">
                <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-text-base">Investment summary</h2>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-text-base">Total investment</span>
                                <span class="font-semibold text-text-base">
                                    {{ $proposal->total_investment ? '$' . number_format($proposal->total_investment, 2) : '—' }}
                                </span>
                            </div>
                        </div>

                        @if ($paymentSchedule->isNotEmpty())
                            <div class="mt-4 border-t border-border-default/70 pt-4">
                                <p class="text-xs uppercase tracking-wide text-text-subtle">Payment schedule</p>
                                <div class="mt-3 space-y-2 text-sm">
                                    @foreach ($paymentSchedule as $item)
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-text-base">
                                                    {{ $item->label ?? 'Installment' }}
                                                    <span class="text-text-subtle font-normal">·</span>
                                                    <span class="text-text-base font-semibold">{{ !empty($item->amount) ? '$' . number_format((float) $item->amount, 2) : '—' }}</span>
                                                </div>
                                                @if (!empty($item->due_trigger))
                                                    <div class="text-xs text-text-base">{{ str_replace('_', ' ', $item->due_trigger) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (!empty($maintenancePlan['enabled']))
                            <div class="mt-4 border-t border-border-default/70 pt-4">
                                <p class="text-xs uppercase tracking-wide text-text-subtle">Maintenance & hosting</p>
                                <div class="mt-2 text-sm text-text-base">
                                    {{ !empty($maintenancePlan['monthly_amount']) ? '$' . number_format((float) $maintenancePlan['monthly_amount'], 2) . ' / month' : 'Custom monthly plan' }}
                                </div>
                                @if (!empty($maintenancePlan['includes']))
                                    <ul class="mt-2 space-y-1 text-xs text-text-base">
                                        @foreach ($maintenancePlan['includes'] as $item)
                                            <li>• {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if (!empty($maintenancePlan['cancellation_terms']))
                                    <div class="text-xs text-text-base mt-2">{{ $maintenancePlan['cancellation_terms'] }}</div>
                                @endif
                            </div>
                        @endif

                        @if ($proposal->payment_policy)
                            <div class="mt-4 border-t border-border-default/70 pt-4">
                                <p class="text-xs uppercase tracking-wide text-text-subtle">Payment policy</p>
                                <p class="text-xs text-text-base mt-2">{{ $proposal->payment_policy }}</p>
                            </div>
                        @endif
                        @if ($proposal->contract)
                            <div class="mt-4 border-t border-border-default/70 pt-4">
                                <p class="text-xs uppercase tracking-wide text-text-subtle">Contract</p>
                                <p class="text-xs text-text-base mt-2">A contract is attached to this proposal.</p>
                            </div>
                        @endif
                    </section>
                </aside>
            </div>

            <section class="oh-card border border-border-default/60 rounded-2xl bg-surface-card/95 p-6 shadow-sm">
                <h2 class="text-base font-semibold text-text-base">Approve & sign</h2>
                <p class="text-sm text-text-base mt-1">We’ll email you a copy and notify the team once it’s signed.</p>

                @if ($signed || $signature)
                    <div class="mt-4 text-sm text-text-base">
                        <strong>Signed</strong> by {{ $signature?->signer_name ?? $proposal->acceptance?->signed_name ?? 'Client' }}
                        @if ($signature?->signed_at || $proposal->approved_at)
                            on {{ optional($signature?->signed_at ?? $proposal->approved_at)->format('M j, Y') }}
                        @endif
                    </div>
                @else
                    <form action="{{ route('proposal.public.sign', $proposal->public_token ?? $proposal->unique_share_token) }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="oh-label">Full name</label>
                                <input name="signer_name" value="{{ old('signer_name', $proposal->client?->full_name ?? '') }}" required class="oh-input h-10 w-full">
                                @error('signer_name')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="oh-label">Email</label>
                                <input type="email" name="signer_email" value="{{ old('signer_email', $proposal->client?->email ?? '') }}" class="oh-input h-10 w-full">
                                @error('signer_email')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label class="oh-label">Typed signature</label>
                            <input name="signature_text" value="{{ old('signature_text', $proposal->client?->full_name ?? '') }}" required class="oh-input h-10 w-full">
                            @error('signature_text')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-text-base">
                            <input type="checkbox" name="agreed" value="1" required>
                            I agree this acts as my electronic signature.
                        </label>
                        @error('agreed')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <div class="pt-2">
                            <button type="submit" class="oh-btn oh-btn--primary">Approve & Sign</button>
                        </div>
                    </form>
                @endif
            </section>

        </div>
    </div>

    @push('head')
        <style>
            @media print {
                body { background: #fff !important; }
                .oh-btn, form, input, label, button { display: none !important; }
                .oh-card { box-shadow: none !important; border-color: #e5e7eb !important; }
            }
        </style>
    @endpush
@endsection
