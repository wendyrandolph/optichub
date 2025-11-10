
@props(['type' => 'info'])
@php $map = [
  'success' => 'badge badge-success',
  'warning' => 'badge badge-warning',
  'danger'  => 'badge badge-danger',
  'info'    => 'badge badge-info',
]; @endphp
<span {{ $attributes->merge(['class' => $map[$type] ?? $map['info']]) }}>{{ $slot }}</span>
