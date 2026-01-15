@props([
    'title' => null,
    'actions' => null,
])

<div {{ $attributes->class(['rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60']) }}>
    @if ($title || $actions)
        <div
            class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5 lg:px-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            <div class="shrink-0">{{ $actions }}</div>
        </div>
    @endif

    <div class="px-4 py-4 sm:px-5 lg:px-6">
        {{ $slot }}
    </div>
</div>
