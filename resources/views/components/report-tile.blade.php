@props([
    'title',
    'icon' => 'fa-clipboard-list',
    'stat' => null,
    'substat' => null,
    'href' => null,
    'colorType' => 'secondary', // brand|secondary|accent|success|warning|danger|info
])

@php
    $accentVars = [
        'brand' => 'var(--brand-primary)',
        'secondary' => 'var(--brand-secondary)',
        'accent' => 'var(--brand-accent)',
        'success' => 'var(--status-success)',
        'warning' => '255 193 7',
        'danger' => '220 53 69',
        'info' => '151 169 216',
    ];
    $rgb = $accentVars[$colorType] ?? $accentVars['secondary'];
    $Tag = $href ? 'a' : 'div';
@endphp

<{{ $Tag }} @if ($href) href="{{ $href }}" @endif
    class="oh-card relative overflow-hidden group focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-secondary/60"
    style="--tile-accent: rgb({{ $rgb }});">
    <span class="absolute inset-y-0 left-0 w-[4px] rounded-l-card" style="background: var(--tile-accent);"></span>

    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="inline-grid h-9 w-9 place-items-center rounded-lg border border-[rgb(var(--border-default))]"
                style="background: color-mix(in oklab, var(--tile-accent) 18%, transparent); color: var(--tile-accent);">
                <i class="fa-solid {{ $icon }}"></i>
            </div>
            <div class="leading-tight">
                <div class="text-sm font-medium text-text-base">{{ $title }}</div>
                @if ($substat)
                    <div class="text-[12px] mt-0.5 text-text-subtle">{{ $substat }}</div>
                @endif
            </div>
        </div>

        @if ($stat)
            <div class="text-2xl font-semibold text-text-base tabular-nums">{{ $stat }}</div>
        @endif
    </div>
    </{{ $Tag }}>
