
@props(['variant' => 'primary'])
@php
  $base = 'btn focus-ring';
  $map = [
    'primary' => 'btn-primary',
    'ghost' => 'btn-ghost',
    'danger' => 'bg-danger text-white hover:bg-red-700',
  ];
@endphp
<button {{ $attributes->merge(['class' => $base.' '.($map[$variant] ?? $map['primary'])]) }}>
  {{ $slot }}
</button>
