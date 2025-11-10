
@props(['label' => null])
@if($label)
  <label class="label">{{ $label }}</label>
@endif
<select {{ $attributes->merge(['class' => 'select']) }}>
  {{ $slot }}
</select>
@error($attributes->get('name'))
  <p class="mt-1 text-xs text-danger">{{ $message }}</p>
@enderror
