@props(['current' => 1])

@php
    $steps = [
        1 => 'Welcome',
        2 => 'Brand',
        3 => 'Logo',
        4 => 'First Client',
        5 => 'First Project',
        6 => 'Team',
        7 => 'Finish',
    ];
@endphp

<div class="mb-6">
    <p class="text-[11px] uppercase tracking-wide text-text-subtle mb-1">
        Step {{ $current }} of {{ count($steps) }}
    </p>
    <div class="flex flex-wrap gap-2 text-xs">
        @foreach ($steps as $num => $label)
            @php
                $isActive = $num === $current;
            @endphp
            <span
                class="px-2 py-1 rounded-full border text-[11px]
                {{ $isActive ? 'bg-surface text-text-base border-border-default' : 'bg-surface-card text-text-subtle border-border-default' }}">
                {{ $num }}. {{ $label }}
            </span>
        @endforeach
    </div>
</div>
