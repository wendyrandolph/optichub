<div {{ $attributes->merge(['class' => 'w-full h-full grid place-items-center']) }}>
    <p class="text-sm text-muted bg-gray-50 dark:bg-gray-800/40 border border-border-default rounded-lg px-3 py-2">
        {{ $message ?? 'No data to display.' }}
    </p>
</div>
