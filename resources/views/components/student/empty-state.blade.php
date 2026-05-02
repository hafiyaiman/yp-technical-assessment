@props([
    'title',
    'description',
])

<x-card>
    <div class="py-10 text-center">
        <p class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $title }}</p>
        <p class="mt-2 text-sm text-zinc-500 dark:text-dark-300">{{ $description }}</p>
    </div>
</x-card>
