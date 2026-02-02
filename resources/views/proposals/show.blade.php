@extends('layouts.app')

@section('title', $proposal->title ?? 'Proposal')

@section('content')
    @php
        $tp = request()->route('tenant') ?? (auth()->user()->tenant ?? auth()->user()->tenant_id);
        $tenantId = $tp instanceof \App\Models\Tenant ? $tp->getKey() : (int) $tp;
        $clientName = $proposal->recipientName();
        $legacyContent = $proposal->content ?? [];
        $paymentSchedule = $paymentSchedule ?? ($proposal->paymentScheduleItems ?? collect());
        $maintenancePlan = $proposal->maintenance_plan ?? [];
        $timeline = $proposal->timeline ?? [];
        $goalItems = $proposal->items->where('type', 'goal');
        $deliverableItems = $proposal->items->where('type', 'deliverable');
    @endphp

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <header class="space-y-4">
            <a href="{{ route('tenant.proposals.index', ['tenant' => $tenantId]) }}" class="oh-btn w-fit">
                <i class="fa-solid fa-arrow-left mr-2 text-[12px]"></i>
                Back to proposals
            </a>
            <div>
                <h1 class="text-2xl md:text-3xl font-semibold text-text-base">{{ $proposal->title }}</h1>
                <p class="text-sm text-text-base mt-2">Prepared for {{ $clientName }}</p>
                <p class="text-sm text-text-base">Prepared by {{ $proposal->tenant?->name ?? 'Workspace' }}</p>
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span class="{{ $proposal->statusPillClass() }}">{{ $proposal->statusLabel() }}</span>
                    @if ($proposal->sent_at)
                        <span class="text-xs text-text-subtle">Sent {{ optional($proposal->sent_at)->format('M j, Y') }}</span>
                    @endif
                </div>
            </div>
        </header>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                @if (strtolower((string) $proposal->status) === 'draft')
                    <button type="submit" form="proposal-send-form" class="oh-btn oh-btn--primary">Send proposal</button>
                @else
                    <a href="{{ route('tenant.proposals.pdf', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                        class="oh-btn oh-btn--primary">Download PDF</a>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if (strtolower((string) $proposal->status) !== 'approved')
                    <a href="{{ route('tenant.proposals.edit', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                        class="oh-btn">Edit</a>
                @endif
                <a href="{{ route('tenant.proposals.pdf', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                    class="oh-btn oh-btn--ghost">Download PDF</a>
                <details class="relative">
                    <summary class="oh-btn oh-btn--ghost list-none cursor-pointer">More actions</summary>
                    <div
                        class="absolute right-0 mt-2 w-56 oh-card border border-border-default/70 rounded-2xl bg-surface-card/95 p-2 shadow-lg space-y-1">
                        @if (strtolower((string) $proposal->status) === 'approved')
                            <form method="POST"
                                action="{{ route('tenant.proposals.duplicate', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}">
                                @csrf
                                <button type="submit" class="oh-btn w-full justify-start">Duplicate as new version</button>
                            </form>
                        @endif
                        <form method="POST"
                            action="{{ route('tenant.proposals.templates.store', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                            data-template-save>
                            @csrf
                            <input type="hidden" name="name" value="">
                            <button type="button" class="oh-btn w-full justify-start" data-template-save-trigger>Save as
                                template</button>
                        </form>
                        @php
                            $publicToken = $proposal->public_token ?? $proposal->unique_share_token;
                        @endphp
                        @if ($publicToken && strtolower((string) $proposal->status) !== 'draft')
                            <button type="button" class="oh-btn w-full justify-start" data-copy-link
                                data-url="{{ route('proposal.public.show', $publicToken) }}">
                                Copy public link
                            </button>
                        @elseif ($publicToken && strtolower((string) $proposal->status) === 'draft')
                            <button type="button" class="oh-btn w-full justify-start" data-copy-link
                                data-url="{{ route('proposal.public.show', $publicToken) }}?preview=1">
                                Copy internal preview link
                            </button>
                        @else
                            <button type="button" class="oh-btn w-full justify-start" disabled>
                                Send to enable public link
                            </button>
                        @endif
                        <a href="{{ route('tenant.proposals.pdf', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                            class="oh-btn oh-btn--ghost w-full justify-start">Download PDF</a>
                        <button type="button" class="oh-btn oh-btn--ghost w-full justify-start"
                            onclick="window.print()">Print</button>
                        @if ($proposal->approved_pdf_path)
                            <a href="{{ asset('storage/' . $proposal->approved_pdf_path) }}"
                                class="oh-btn w-full justify-start">Download signed PDF</a>
                        @endif
                        @php
                            $isDraft = strtolower((string) $proposal->status) === 'draft';
                        @endphp
                        <form method="POST"
                            action="{{ route('tenant.proposals.archive', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                            onsubmit="return confirm('{{ $isDraft ? 'Delete this draft proposal? This cannot be undone.' : 'Archive this proposal? You can restore it from the database if needed.' }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="oh-btn oh-btn--ghost w-full justify-start text-rose-600">
                                {{ $isDraft ? 'Delete proposal' : 'Archive proposal' }}
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8 space-y-6">
                @if ($proposal->summary)
                    <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/95 p-7">
                        <h2 class="text-lg font-semibold text-text-base">Purpose / overview</h2>
                        <p class="text-sm text-text-subtle mt-3">{{ $proposal->summary }}</p>
                    </section>
                @endif

                @if ($goalItems->isNotEmpty() || data_get($legacyContent, 'goals'))
                    <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/90 p-6">
                        <h2 class="text-base font-semibold text-text-base">Goals</h2>
                        <ul class="mt-3 space-y-2 text-sm text-text-base">
                            @forelse ($goalItems as $goal)
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-[12px] text-text-subtle mt-1"></i>
                                    <div>
                                        <div class="font-medium text-text-base">{{ $goal->title }}</div>
                                        @if ($goal->description)
                                            <div class="text-xs text-text-subtle mt-1">{{ $goal->description }}</div>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-text-subtle">{{ data_get($legacyContent, 'goals') }}</li>
                            @endforelse
                        </ul>
                    </section>
                @endif

                @if ($deliverableItems->isNotEmpty() || data_get($legacyContent, 'objectives'))
                    <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/90 p-6">
                        <h2 class="text-base font-semibold text-text-base">Objectives & deliverables</h2>
                        <ul class="mt-3 space-y-2 text-sm text-text-base">
                            @forelse ($deliverableItems as $deliverable)
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-check text-[12px] text-text-subtle mt-1"></i>
                                    <div>
                                        <div class="font-medium text-text-base">{{ $deliverable->title }}</div>
                                        @if ($deliverable->description)
                                            <div class="text-xs text-text-subtle mt-1">{{ $deliverable->description }}
                                            </div>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-text-subtle">{{ data_get($legacyContent, 'objectives') }}</li>
                            @endforelse
                        </ul>
                    </section>
                @endif

                @if (!empty($timeline) || data_get($legacyContent, 'timeline'))
                    <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/90 p-6">
                        <h2 class="text-base font-semibold text-text-base">Timeline</h2>
                        <div class="mt-3 space-y-3 text-sm">
                            @forelse ($timeline as $phase)
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-medium text-text-base">{{ $phase['phase'] ?? 'Phase' }}</div>
                                        <div class="text-xs text-text-subtle">{{ $phase['description'] ?? '' }}</div>
                                    </div>
                                    <div class="text-xs text-text-subtle">{{ $phase['duration'] ?? '' }}</div>
                                </div>
                            @empty
                                <div class="text-sm text-text-subtle">{{ data_get($legacyContent, 'timeline') }}</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($proposal->next_steps)
                    <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/90 p-6">
                        <h2 class="text-base font-semibold text-text-base">Next steps</h2>
                        <p class="text-sm text-text-subtle mt-2">{{ $proposal->next_steps }}</p>
                    </section>
                @endif

                @if (strtolower((string) $proposal->status) === 'draft')
                    <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/90 p-6">
                        <h2 class="text-base font-semibold text-text-base">Contract (optional)</h2>
                        <p class="text-sm text-text-subtle mt-2">Attach a contract to send with this proposal.</p>
                        <form id="proposal-send-form" method="POST" enctype="multipart/form-data"
                            action="{{ route('tenant.proposals.send', ['tenant' => $tenantId, 'proposal' => $proposal->id]) }}"
                            class="mt-4 space-y-4">
                            @csrf
                            <div class="flex flex-wrap items-center gap-3 text-xs text-text-subtle">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="contract_mode" value="none" checked>
                                    No contract
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="contract_mode" value="template">
                                    Attach template
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="contract_mode" value="upload">
                                    Upload contract
                                </label>
                            </div>
                            <div class="grid gap-2 md:grid-cols-2">
                                <select name="contract_template_id" class="oh-select h-10 w-full">
                                    <option value="">Select a template</option>
                                    @foreach ($contractTemplates ?? [] as $template)
                                        <option value="{{ $template->id }}">{{ $template->title }}</option>
                                    @endforeach
                                </select>
                                <input type="file" name="contract_upload" class="oh-input h-10 w-full">
                            </div>
                        </form>
                    </section>
                @endif
            </div>

            <aside class="lg:col-span-4 space-y-6">
                <section class="oh-card border border-border-default/50 rounded-2xl bg-surface-card/85 p-5">
                    <h2 class="text-sm font-semibold text-text-base">Investment summary</h2>
                    <div class="mt-3 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-text-subtle">Total investment</span>
                            <span class="font-semibold text-text-base text-sm">
                                {{ $proposal->total_investment ? '$' . number_format($proposal->total_investment, 2) : '—' }}
                            </span>
                        </div>
                    </div>

                    @if ($paymentSchedule->isNotEmpty())
                        <div class="mt-4 border-t border-border-default/60 pt-4">
                            <p class="text-xs uppercase tracking-wide text-text-subtle">Payment schedule</p>
                            <div class="mt-3 space-y-2 text-sm">
                                @foreach ($paymentSchedule as $item)
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-medium text-text-base text-sm">
                                                {{ $item->label ?? 'Installment' }}
                                                <span class="text-text-subtle font-normal">·</span>
                                                <span class="text-text-base font-semibold text-sm">
                                                    {{ !empty($item->amount) ? '$' . number_format((float) $item->amount, 2) : '—' }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-text-subtle">
                                                {{ str_replace('_', ' ', $item->due_trigger ?? '') }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (!empty($maintenancePlan['enabled']))
                        <div class="mt-4 border-t border-border-default/60 pt-4">
                            <p class="text-xs uppercase tracking-wide text-text-subtle">Maintenance & hosting</p>
                            <div class="mt-2 text-sm text-text-base">
                                {{ !empty($maintenancePlan['monthly_amount']) ? '$' . number_format((float) $maintenancePlan['monthly_amount'], 2) . ' / month' : 'Custom monthly plan' }}
                            </div>
                            @if (!empty($maintenancePlan['includes']))
                                <ul class="mt-2 space-y-1 text-xs text-text-subtle">
                                    @foreach ($maintenancePlan['includes'] as $item)
                                        <li>• {{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    @if ($proposal->payment_policy)
                        <div class="mt-4 border-t border-border-default/60 pt-4">
                            <p class="text-xs uppercase tracking-wide text-text-subtle">Payment policy</p>
                            <p class="text-xs text-text-subtle mt-2">{{ $proposal->payment_policy }}</p>
                        </div>
                    @endif
                </section>
                @if ($proposal->acceptance)
                    <section class="oh-card border border-border-default/70 rounded-2xl bg-surface-card/90 p-6">
                        <h2 class="text-base font-semibold text-text-base">Approval details</h2>
                        <div class="mt-3 text-sm text-text-subtle space-y-1">
                            <div>Signed by {{ $proposal->acceptance->signed_name }}</div>
                            <div>{{ $proposal->acceptance->email }}</div>
                            <div>{{ optional($proposal->acceptance->accepted_at)->format('M j, Y g:i a') }}</div>
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('click', function(event) {
                const trigger = event.target.closest('[data-template-save-trigger]');
                if (!trigger) return;
                const form = trigger.closest('[data-template-save]');
                if (!form) return;
                const name = window.prompt('Template name');
                if (!name) return;
                const input = form.querySelector('input[name="name"]');
                if (!input) return;
                input.value = name.trim();
                if (!input.value) return;
                form.submit();
            });
        </script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('click', function(event) {
                const btn = event.target.closest('[data-copy-link]');
                if (!btn) return;
                const url = btn.getAttribute('data-url');
                if (!url) return;
                navigator.clipboard.writeText(url).then(() => {
                    btn.textContent = 'Link copied';
                    setTimeout(() => btn.textContent = 'Copy public link', 1500);
                });
            });
        </script>
    @endpush
@endsection
