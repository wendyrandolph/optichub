@props(['title', 'icon' => 'fa-clipboard-list'])

<div class="oh-card report-section transition-colors">
    {{-- Header --}}
    <h2 class="oh-section-title flex items-center gap-2 mb-3">
        <i class="fa-solid {{ $icon }} text-[rgb(var(--brand-primary))]"></i>
        <span>{{ $title }}</span>
    </h2>

    {{-- List container --}}
    <ul class="oh-report-list grid gap-2 list-none p-0 m-0">
        {{ $slot }}
    </ul>
</div>
