@props([
    'paginator',
    'position' => 'bottom', // 'top' or 'bottom'
])
@php
    use Illuminate\Pagination\Paginator as SimplePaginator;
    use Illuminate\Pagination\LengthAwarePaginator;

    $isLengthAware = $paginator instanceof LengthAwarePaginator;
    $isSimple = $paginator instanceof SimplePaginator;
@endphp

@if (! $isLengthAware && ! $isSimple)
    @php return; @endphp
@endif

@if (method_exists($paginator, 'hasPages') && $paginator->hasPages())
    @php
        $isTop = $position === 'top';
        $elements = $isLengthAware && method_exists($paginator, 'elements') ? $paginator->elements() : [];
    @endphp

    <div class="{{ $isTop ? 'pb-3' : 'pt-4' }}">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            {{-- Results text (left) --}}
            <p class="text-xs text-text-subtle">
                @if ($paginator->firstItem())
                    Showing <span class="font-semibold text-text-base">{{ $paginator->firstItem() }}</span>
                    to <span class="font-semibold text-text-base">{{ $paginator->lastItem() }}</span>
                    of <span class="font-semibold text-text-base">{{ $paginator->total() }}</span>
                    results
                @else
                    Showing <span class="font-semibold text-text-base">{{ $paginator->count() }}</span> results
                @endif
            </p>

            {{-- Pager (right) --}}
            <div class="flex items-center justify-between sm:justify-end gap-2">
                {{-- Prev --}}
                @if ($paginator->onFirstPage())
                    <span class="oh-btn opacity-50 pointer-events-none">‹</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="oh-btn" rel="prev">‹</a>
                @endif

                @if ($isLengthAware && !empty($elements))
                    {{-- Numbers (keep top one tighter if you want) --}}
                    <div
                        class="inline-flex items-center rounded-lg overflow-hidden border border-border-default/70 bg-surface-card">
                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span class="px-3 py-2 text-xs text-text-subtle">{{ $element }}</span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <span class="px-3 py-2 text-xs font-semibold text-white"
                                            style="background: rgb(var(--brand-primary));">
                                            {{ $page }}
                                        </span>
                                    @else
                                        <a href="{{ $url }}"
                                            class="px-3 py-2 text-xs text-text-base hover:bg-surface-muted/60">
                                            {{ $page }}
                                        </a>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="oh-btn" rel="next">›</a>
                @else
                    <span class="oh-btn opacity-50 pointer-events-none">›</span>
                @endif
            </div>
        </div>
    </div>
@endif
