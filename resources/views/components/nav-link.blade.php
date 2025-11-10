
@props(['active' => false])
@php
$classes = 'flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-ink-100';
$activeClasses = $active ? 'bg-ink-100 text-ink-900 font-medium' : 'text-ink-700';
@endphp
<a {{ $attributes->merge(['class' => $classes.' '.$activeClasses]) }}>
  {{ $slot }}
</a>
