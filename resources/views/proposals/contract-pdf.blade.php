<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Contract</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: 700; }
        .muted { color: #6b7280; font-size: 11px; }
        .section { margin-bottom: 16px; }
        .section h3 { font-size: 13px; margin: 0 0 6px; }
        .box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $contract->title ?? 'Contract' }}</div>
        <div class="muted">{{ $tenant?->name ?? 'Renlo' }}</div>
    </div>

    <div class="section box">
        @if (!empty($contract->snapshot_html))
            {!! $contract->snapshot_html !!}
        @elseif (!empty($contract->snapshot_json))
            @foreach ($contract->snapshot_json as $section)
                <h3>{{ $section['title'] ?? 'Section' }}</h3>
                <p>{{ $section['content'] ?? '' }}</p>
            @endforeach
        @else
            <p>Contract snapshot is stored as an upload.</p>
        @endif
    </div>

    @if ($contract->signed_at)
        <div class="section">
            <h3>Signature</h3>
            <p>Signed by {{ $contract->signer_name ?? 'Client' }} on {{ optional($contract->signed_at)->format('M j, Y') }}</p>
        </div>
    @endif
</body>
</html>
