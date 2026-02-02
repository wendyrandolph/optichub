<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal PDF</title>
    <style>
        body { font-family: Montserrat, DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; margin-bottom: 6px; }
        p, li { line-height: 1.5; }
        ul { padding-left: 18px; }
        .section { margin-bottom: 12px; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        .summary { margin-bottom: 16px; }
        .grid { display: table; width: 100%; }
        .grid > div { display: table-cell; vertical-align: top; }
        .right { width: 36%; padding-left: 16px; }
        .left { width: 64%; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px; }
        .row { display: flex; justify-content: space-between; }
        .row span { display: inline-block; }
    </style>
</head>
<body>
    @php
        $tenant = $proposal->tenant ?? null;
        $primary = $tenant?->primary_color ?: '#1F3C66';
        $logoPath = $tenant?->logo_path ? public_path('storage/' . $tenant->logo_path) : null;
        $clientName = $proposal->recipientName();
        $legacyContent = $proposal->content ?? [];
        $goalItems = $proposal->items->where('type', 'goal');
        $deliverableItems = $proposal->items->where('type', 'deliverable');
        $paymentSchedule = $paymentSchedule ?? ($proposal->paymentScheduleItems ?? collect());
        $timeline = $proposal->timeline ?? [];
    @endphp

    <div class="meta">
        @if ($logoPath && file_exists($logoPath))
            <img src="file://{{ $logoPath }}" alt="Logo" style="height:40px;margin-bottom:8px;">
        @else
            <div style="font-weight:700;color:{{ $primary }};">Renlo</div>
        @endif
    </div>
    <h1>{{ $proposal->title }}</h1>
    <div class="meta">Prepared for {{ $clientName }}</div>

    <div class="grid">
        <div class="left">
            @if ($proposal->summary)
                <div class="section">
                    <h2>Purpose / Overview</h2>
                    <p>{{ $proposal->summary }}</p>
                </div>
            @endif

            @if ($goalItems->isNotEmpty() || data_get($legacyContent, 'goals'))
                <div class="section">
                    <h2>Goals</h2>
                    <ul>
                        @forelse ($goalItems as $goal)
                            <li>{{ $goal->title }}@if($goal->description) — {{ $goal->description }}@endif</li>
                        @empty
                            <li>{{ data_get($legacyContent, 'goals') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endif

            @if ($deliverableItems->isNotEmpty() || data_get($legacyContent, 'objectives'))
                <div class="section">
                    <h2>Objectives / Deliverables</h2>
                    <ul>
                        @forelse ($deliverableItems as $deliverable)
                            <li>{{ $deliverable->title }}@if($deliverable->description) — {{ $deliverable->description }}@endif</li>
                        @empty
                            <li>{{ data_get($legacyContent, 'objectives') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endif

            @if (!empty($timeline) || data_get($legacyContent, 'timeline'))
                <div class="section">
                    <h2>Timeline</h2>
                    @if (!empty($timeline))
                        <ul>
                            @foreach ($timeline as $phase)
                                <li>{{ $phase['phase'] ?? 'Phase' }} — {{ $phase['duration'] ?? '' }} {{ $phase['description'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p>{{ data_get($legacyContent, 'timeline') }}</p>
                    @endif
                </div>
            @endif

            @if ($proposal->next_steps)
                <div class="section">
                    <h2>Next Steps</h2>
                    <p>{{ $proposal->next_steps }}</p>
                </div>
            @endif
        </div>
        <div class="right">
            <div class="box">
                <h2>Investment</h2>
                <div class="row">
                    <span>Total</span>
                    <span>{{ $proposal->total_investment ? '$' . number_format($proposal->total_investment, 2) : '—' }}</span>
                </div>

                @if ($paymentSchedule->isNotEmpty())
                    <h2>Payment Schedule</h2>
                    <ul>
                        @foreach ($paymentSchedule as $item)
                            <li>
                                <strong>{{ $item->label ?? 'Installment' }}</strong>
                                <span> · </span>
                                <span>{{ !empty($item->amount) ? '$' . number_format((float) $item->amount, 2) : '—' }}</span>
                                @if (!empty($item->due_trigger))
                                    <span style="color:#6b7280;"> ({{ str_replace('_', ' ', $item->due_trigger) }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (!empty($proposal->payment_policy))
                    <h2>Payment Policy</h2>
                    <p>{{ $proposal->payment_policy }}</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
