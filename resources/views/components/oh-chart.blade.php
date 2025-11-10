@php
    use Illuminate\Support\Str;
@endphp

@props([
    'id' => 'ch_' . Str::random(6),
    'config' => [],
    'class' => 'w-full h-56',
])

<div class="{{ $class }}">
    <canvas id="{{ $id }}" data-chart></canvas>
    <script type="application/json">
    @json($config, JSON_UNESCAPED_SLASHES)
  </script>
</div>
