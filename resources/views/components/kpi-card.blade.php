@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => 'fa-star', // e.g. 'fa-building', 'fa-diagram-project'
    'href' => null, // if null, renders a <div> instead of <a>
    'colorType' => 'secondary', // brand | secondary | accent | success | warning | danger | info
])

@php
    // Map semantic type -> CSS var for the left accent bar
    $accentVars = [
        'brand' => 'var(--brand-primary)',
        'secondary' => 'var(--brand-secondary)',
        'accent' => 'var(--brand-accent)',
        'success' => 'var(--status-success)',
        'warning' => '255 193 7', // #FFC107
        'danger' => '220 53 69', // #DC3545
        'info' => '151 169 216', // soft blue for neutral info
    ];
    $rgb = $accentVars[$colorType] ?? $accentVars['secondary'];

    $Wrapper = $href ? 'a' : 'div';
@endphp

<{{ $Wrapper }} @if ($href) href="{{ $href }}" @endif
    class="oh-kpi group focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-secondary/60"
    style="--kpi-accent: rgb({{ $rgb }});">
    <div class="oh-kpi__top">
        <div class="flex items-center gap-3">
            <div
                class="inline-grid h-9 w-9 place-items-center rounded-lg
                        border border-optic-border bg-surface-card/70">
                {{-- FA6+ solid by default; pass full class if you need regular/brands --}}
                <i class="fa-solid {{ $icon }} oh-kpi__icon"></i>
            </div>
            <div class="leading-tight">
                <div class="oh-kpi__label">{{ $title }}</div>
                @if ($subtitle)
                    <div class="text-[12px] mt-0.5" style="color: rgb(var(--text-subtle));">
                        {{ $subtitle }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Value --}}
        <div class="oh-kpi__value tabular-nums">
            {{ $value }}
        </div>
    </div>
    </{{ $Wrapper }}>
