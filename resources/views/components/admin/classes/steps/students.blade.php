@props([
    'students' => collect(),
    'studentIds' => [],
    'editingId' => null,
])

<x-step.items step="3" title="Students" description="Enroll students">
    <div class="pt-0">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Students</p>
                <p class="text-xs text-zinc-500 dark:text-dark-300">
                    Search and tick students who should belong to this class.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-2 pt-2">
            <x-input wire:model.live.debounce.500ms="studentSearch" icon="magnifying-glass"
                placeholder="Name or email..." />

        </div>

        <div
            class="mt-3 grid max-h-80 gap-2 overflow-y-auto rounded-md border border-zinc-200 p-2 dark:border-dark-600">
            @forelse ($students as $student)
                <label
                    class="flex cursor-pointer items-start justify-between gap-3 rounded-md border border-zinc-200 px-3 py-2 transition hover:border-primary-300 hover:bg-primary-50 dark:border-dark-600 dark:hover:border-primary-500 dark:hover:bg-dark-700">
                    <div class="min-w-0">
                        <x-checkbox wire:model="studentIds" :value="$student->id" :label="$student->name" />
                        <p class="ml-7 mt-0.5 truncate text-xs text-zinc-500 dark:text-dark-300">
                            {{ $student->email }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-zinc-500 dark:text-dark-300">
                        {{ $student->schoolClass?->id === $editingId ? 'Current class' : $student->schoolClass?->name ?? 'No class' }}
                    </span>
                </label>
            @empty
                <p class="px-2 py-3 text-sm text-zinc-500 dark:text-dark-300">
                    No students found.
                </p>
            @endforelse
        </div>
    </div>
</x-step.items>
