@props(['title', 'icon' => 'fa-clipboard-list'])

<div class="oh-card p-4">
    {{-- Header --}}
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid {{ $icon }} text-[rgb(var(--brand-primary))]"></i>
        <h3 class="text-sm font-semibold text-text-base">
            <span>{{ $title }}</span>
        </h3>
    </div>

    {{-- Content --}}
    <ul class="oh-report-list space-y-2 list-none p-0 m-0">
        {{ $slot }}
    </ul>
</div>
