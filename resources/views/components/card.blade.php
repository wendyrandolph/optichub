
@props(['title' => null, 'actions' => null])
<div {{ $attributes->merge(['class' => 'card']) }}>
  @if($title || $actions)
    <div class="card-header flex items-center justify-between">
      <div>{{ $title }}</div>
      <div>{{ $actions }}</div>
    </div>
  @endif
  <div class="card-body">
    {{ $slot }}
  </div>
</div>
