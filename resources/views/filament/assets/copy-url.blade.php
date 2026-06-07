<div class="space-y-2">
    <div class="text-sm text-gray-600 dark:text-gray-300">
        Click the text to copy.
    </div>

    <div
        x-data
        class="w-full rounded-xl border px-3 py-2 text-sm break-all cursor-pointer"
        x-on:click="navigator.clipboard.writeText(@js($url)).then(() => $tooltip('Copied!'))"
    >
        {{ $url }}
    </div>
</div>