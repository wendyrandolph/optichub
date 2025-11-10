
@props(['label' => null, 'help' => null])
@if($label)
  <label class="label">{{ $label }}</label>
@endif
<input {{ $attributes->merge(['class' => 'input']) }} />
@if($help)
  <p class="mt-1 text-xs text-ink-500">{{ $help }}</p>
@endif
@error($attributes->get('name'))
  <p class="mt-1 text-xs text-danger">{{ $message }}</p>
@enderror
