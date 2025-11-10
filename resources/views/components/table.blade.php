<script type="text/plain" data-filename="resources/views/components/table.blade.php">
<table {{ $attributes->merge(['class' => 'min-w-full border-separate border-spacing-0 text-sm']) }}>
  <thead class="text-left text-ink-600">
    <tr>
      {{ $head }}
    </tr>
  </thead>
  <tbody class="bg-white">
    {{ $slot }}
  </tbody>
</table>
</script>