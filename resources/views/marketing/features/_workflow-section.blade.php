@props([
    'id' => null,
    'eyebrow' => '',
    'title' => '',
    'body' => '',
    'bullets' => [],
    'img' => '',
    'alt' => '',
    'reverse' => false,
])

<article id="{{ $id }}" class="workflow-section grid gap-8 items-center lg:grid-cols-2 {{ $reverse ? 'lg:flex-row-reverse' : '' }}">
    <div class="space-y-4">
        <p class="text-xs uppercase tracking-[0.3em] text-text-subtle">{{ $eyebrow }}</p>
        <h2 class="text-3xl font-semibold text-text-base leading-tight">{{ $title }}</h2>
        <p class="text-base text-text-subtle max-w-2xl leading-relaxed">{{ $body }}</p>
        <ul class="space-y-2 text-sm text-text-subtle list-disc list-inside">
            @foreach ($bullets as $bullet)
                <li>{{ $bullet }}</li>
            @endforeach
        </ul>
    </div>
    <div class="oh-card oh-card--muted p-0">
        <figure class="screenshot-frame m-0">
            <img src="{{ asset($img) }}" alt="{{ $alt }}" class="object-cover w-full h-full">
        </figure>
    </div>
</article>
