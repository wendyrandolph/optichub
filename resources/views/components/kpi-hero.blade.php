@props([
    'label',
    'value',
    'icon',
    'color' => 'brand', // brand | success | accent | secondary
    'href' => null,
    'subtitle' => null,
])

@php
    $Wrapper = $href ? 'a' : 'div';

    $schemes = [
        'brand' => ['edge' => 'var(--ui-primary-edge)', 'tint' => 'var(--ui-primary-tint)'],
        'accent' => ['edge' => 'var(--ui-accent-edge)', 'tint' => 'var(--ui-accent-tint)'],
        'secondary' => ['edge' => 'var(--ui-lavender-edge)', 'tint' => 'var(--ui-lavender-tint)'],
        'success' => ['edge' => 'var(--ui-success)', 'tint' => 'var(--ui-accent-tint)'],
    ];
    $scheme = $schemes[$color] ?? $schemes['secondary'];
    $edge = $scheme['edge'];
    $tint = $scheme['tint'];
@endphp

@if ($href)
    <a href="{{ $href }}"
        class="group relative w-full rounded-2xl px-5 py-5 ring-1 ring-[rgb(var(--ui-border)/0.6)]
           transition hover:shadow-md bg-[var(--hero-tint)]
           focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--ui-primary)/0.55)]"
        style="--hero-accent: rgb({{ $edge }}); --hero-tint: rgb({{ $tint }}); border-left: 4px solid var(--hero-accent);">
@else
    <div class="group relative w-full rounded-2xl px-5 py-5 ring-1 ring-[rgb(var(--ui-border)/0.6)]
           transition hover:shadow-md bg-[var(--hero-tint)]
           focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--ui-primary)/0.55)]"
        style="--hero-accent: rgb({{ $edge }}); --hero-tint: rgb({{ $tint }}); border-left: 4px solid var(--hero-accent);">
@endif

{{-- Top row: icon (left) + value (right) --}}
<div class="flex items-center justify-between gap-3">
    <div
        class="h-10 w-10 rounded-xl grid place-items-center ring-1 ring-[rgb(var(--ui-border)/0.5)] flex-shrink-0 bg-[rgba(var(--hero-accent-rgb),0.1)]"
        style="--hero-accent-rgb: {{ $edge }};">
        <i class="fa-solid {{ $icon }} text-sm text-[rgb(var(--ui-text))]" style="color: rgb({{ $edge }});"></i>
    </div>
    <div class="text-2xl font-semibold tabular-nums text-text-base leading-none text-right flex-1">
        {{ $value }}
    </div>
</div>

{{-- Second row: label + subtitle --}}
<div class="mt-3 space-y-1">
    <div class="text-xs font-semibold uppercase tracking-wide text-text-subtle">
        {{ $label }}
    </div>

    @if ($subtitle)
        <div class="text-xs text-text-subtle">
            {{ $subtitle }}
        </div>
    @endif
</div>

@if ($href)
    </a>
@else
    </div>
@endif
