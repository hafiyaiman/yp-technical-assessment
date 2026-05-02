@props([
    'logs' => collect(),
])

<x-card>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Activity History</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                Exam-specific events for this paper.
            </p>
        </div>
        <x-badge text="{{ $logs->count() }} events" icon="clock" color="gray" light />
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($logs as $log)
            <div class="flex gap-3 rounded-md border border-zinc-200 p-3 dark:border-dark-600">
                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 dark:bg-dark-700">
                    <x-icon name="clock" class="h-4 w-4 text-zinc-500 dark:text-dark-300" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-medium text-zinc-950 dark:text-white">{{ $log->description }}</p>
                        <span class="shrink-0 text-xs text-zinc-500 dark:text-dark-300">
                            {{ $log->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-dark-300">
                        <x-badge :text="str($log->action)->headline()" color="gray" light />
                        <span>{{ $log->actor?->name ?? 'System' }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-md border border-dashed border-zinc-200 px-4 py-8 text-center dark:border-dark-600">
                <p class="text-sm font-medium text-zinc-950 dark:text-white">No activity yet</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                    Saves, publishing, submissions, and grading will appear here.
                </p>
            </div>
        @endforelse
    </div>
</x-card>
