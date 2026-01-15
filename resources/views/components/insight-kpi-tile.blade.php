@props([
    'title',
    'icon' => 'fa-chart-line',
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
    class="oh-insight-tile group relative overflow-hidden
           rounded-2xl ring-1 ring-[rgb(var(--border)/0.55)]
           bg-[rgb(var(--surface))] text-[rgb(var(--text))]
           focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--brand-secondary))/0.60]"
    style="--tile-accent: rgb({{ $rgb }});">

    {{-- Accent rail (matches Reports tiles) --}}
    <span class="absolute inset-y-0 left-0 w-[4px]" style="background: var(--tile-accent);"></span>

    <div class="p-4 sm:p-5">
        {{-- Row 1: icon + title --}}
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex items-center gap-3">
                <div class="inline-grid h-9 w-9 place-items-center rounded-lg border border-[rgb(var(--border)/0.55)]"
                    style="background: color-mix(in oklab, var(--tile-accent) 16%, transparent); color: var(--tile-accent);">
                    <i class="fa-solid {{ $icon }} text-[14px]"></i>
                </div>

                <div class="min-w-0">
                    <div
                        class="text-[11px] font-semibold uppercase tracking-wide text-[rgb(var(--text-subtle))] truncate">
                        {{ $title }}
                    </div>
                    @if ($substat)
                        <div class="text-xs text-[rgb(var(--text-subtle))] mt-0.5 truncate">
                            {{ $substat }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Row 1: value --}}
            @if (!is_null($stat))
                <div class="text-xl sm:text-2xl font-semibold tabular-nums text-[rgb(var(--text))] leading-none">
                    {{ $stat }}
                </div>
            @endif
        </div>
    </div>
    </{{ $Tag }}>
