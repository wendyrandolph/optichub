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

    $isLink = (bool) $href;
@endphp

<{{ $Tag }} @if ($href) href="{{ $href }}" @endif
    class="oh-card oh-card--interactive relative overflow-hidden group focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--brand-primary)/0.35)]
           {{ $isLink ? 'cursor-pointer transition' : '' }}"
    style="--tile-accent: rgb({{ $rgb }});">

    {{-- left accent rail --}}
    <span class="absolute inset-y-0 left-0 w-[4px]" style="background: var(--tile-accent);"></span>

    {{-- subtle premium hover (no stark flips) --}}
    <span class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
        style="background: radial-gradient(1200px 220px at 20% 0%, rgba(255,255,255,0.55), rgba(255,255,255,0) 60%);">
    </span>

    <div class="relative flex items-start justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0">
            {{-- Icon --}}
            <div class="mt-0.5 inline-grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[rgb(var(--border)/0.65)]
                bg-[rgb(var(--surface))]
                shadow-[0_1px_0_rgba(15,23,42,0.03)]"
                style="background: color-mix(in oklab, var(--tile-accent) 14%, rgb(var(--surface)));
                       color: var(--tile-accent);">
                <i class="fa-solid {{ $icon }} text-[18px]"></i>
            </div>

            {{-- Text --}}
            <div class="leading-tight">
                <div class="text-[13px] font-semibold tracking-tight text-text-base">
                    {{ $title }}
                </div>

                @if ($substat)
                    <div class="text-[11px] mt-0.5 text-text-subtle">
                        {{ $substat }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Stat --}}
        @if (!is_null($stat) && $stat !== '')
            <div class= "text-[24px] font-semibold tracking-tight text-text-base tabular-nums">
                {{ $stat }}
            </div>
        @endif
    </div>

    {{-- Optional: a tiny bottom “sheen” to make tiles feel less flat --}}
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px opacity-70"
        style="background: color-mix(in oklab, var(--tile-accent) 18%, rgba(255,255,255,0));"></div>

    </{{ $Tag }}>
