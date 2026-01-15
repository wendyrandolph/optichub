@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => 'fa-star',
    'href' => null,
    'colorType' => 'secondary',
    'variant' => 'card', // 'tile' | 'card'
])

@php
    $schemes = [
        'brand' => ['edge' => 'var(--ui-primary-edge)', 'tint' => 'var(--ui-primary-tint)'],
        'accent' => ['edge' => 'var(--ui-accent-edge)', 'tint' => 'var(--ui-accent-tint)'],
        'secondary' => ['edge' => 'var(--ui-lavender-edge)', 'tint' => 'var(--ui-lavender-tint)'],
        'success' => ['edge' => 'var(--ui-success)', 'tint' => 'var(--ui-accent-tint)'],
        'warning' => ['edge' => '255 193 7', 'tint' => '241 238 248'],
        'danger' => ['edge' => 'var(--ui-danger)', 'tint' => '241 238 248'],
        'info' => ['edge' => 'var(--ui-primary)', 'tint' => 'var(--ui-primary-tint)'],
        'neutral' => ['edge' => 'var(--ui-border)', 'tint' => 'var(--ui-surface-muted)'],
    ];
    $scheme = $schemes[$colorType] ?? $schemes['secondary'];
    $edge = $scheme['edge'];
    $tint = $scheme['tint'];
    $Wrapper = $href ? 'a' : 'div';
    $isLink = (bool) $href;

    $variantClass = $variant === 'tile' ? 'oh-kpi--tile' : 'oh-kpi--card';
@endphp

<{{ $Wrapper }} @if ($href) href="{{ $href }}" @endif
    class="oh-kpi group block transition {{ $variantClass }}
    {{ $isLink ? 'cursor-pointer hover:brightness-[1.02] active:scale-[0.99]' : '' }}
    focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--ui-primary)/.55)]"
    style="--kpi-edge: rgb({{ $edge }}); --kpi-edge-alpha: rgba({{ $edge }},0.10); --kpi-tint: rgb({{ $tint }}); ">

    {{-- icon + value row --}}
    <div class="oh-kpi__top">
        <div class="oh-kpi__iconWrap">
            <i class="fa-solid {{ $icon }} oh-kpi__icon"></i>
        </div>
        <div class="oh-kpi__label">{{ $title }}</div>
        @if ($subtitle)
            <div class="oh-kpi__sub">{{ $subtitle }}</div>
        @endif


        <div class="oh-kpi__value tabular-nums">{{ $value }}</div>
    </div>



    </{{ $Wrapper }}>
