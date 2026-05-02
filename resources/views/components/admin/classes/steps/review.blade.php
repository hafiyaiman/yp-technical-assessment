@props([
    'name' => '',
    'code' => '',
    'subjectIds' => [],
    'studentIds' => [],
])

<x-step.items step="4" title="Review" description="Confirm setup">
    <div class="grid gap-3 pt-2 sm:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-dark-600">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-dark-300">Class</p>
            <p class="mt-2 font-semibold text-zinc-950 dark:text-white">{{ $name ?: 'Class name not set' }}</p>
            <p class="text-sm text-zinc-500 dark:text-dark-300">{{ $code ?: 'Class code not set' }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-dark-600">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-dark-300">
                Subjects selected
            </p>
            <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ count($subjectIds) }}</p>
        </div>

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-dark-600">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-dark-300">
                Students enrolled
            </p>
            <p class="mt-2 text-2xl font-semibold text-zinc-950 dark:text-white">{{ count($studentIds) }}</p>
        </div>
    </div>
</x-step.items>
