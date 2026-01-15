@php
    $tone = $tone ?? 'muted';
    $toneClasses = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
        'muted' => 'border-border-default bg-surface-muted text-text-subtle',
    ];
    $badgeClass = $toneClasses[$tone] ?? $toneClasses['muted'];
    $displayValue = is_array($value) ? json_encode($value) : $value;
@endphp

<a href="{{ $href }}"
    class="oh-card p-5 space-y-3 hover:bg-surface-accent/40 transition focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-primary)/.25)]">
    <div class="flex items-start justify-between gap-3">
        <div class="text-xs uppercase tracking-wide text-text-subtle">{{ $title }}</div>
        @if (!empty($delta))
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] {{ $badgeClass }}">
                {{ $delta }}
            </span>
        @endif
    </div>
    <div class="text-3xl font-semibold text-text-base tabular-nums">
        {{ $displayValue }}
    </div>
    <div class="text-xs text-text-subtle">
        {{ $helper }}
    </div>
</a>
