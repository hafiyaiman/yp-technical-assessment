@props([
    'subjects' => collect(),
])

<x-step.items step="2" title="Subjects" description="Attach subjects">
    <div class="pt-2">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Subjects</p>
                <p class="text-xs text-zinc-500 dark:text-dark-300">Search by subject name or code.</p>
            </div>
        </div>

        <div class="pt-2">
            <x-input wire:model.live.debounce.500ms="subjectSearch" icon="magnifying-glass"
                placeholder="Mathematics, MATH..." />
        </div>
        <div
            class="mt-2 grid max-h-72 gap-2 overflow-y-auto rounded-md border border-zinc-200 p-2 dark:border-dark-600">
            @forelse ($subjects as $subject)
                <div class="rounded-md border border-zinc-200 px-3 py-2 dark:border-dark-600">
                    <x-checkbox wire:model="subjectIds" :value="$subject->id" :label="$subject->name" />
                </div>
            @empty
                <p class="px-2 py-3 text-sm text-zinc-500 dark:text-dark-300">
                    No subjects found.
                </p>
            @endforelse
        </div>

        <x-input-error :messages="$errors->get('subjectIds')" class="mt-2" />
    </div>
</x-step.items>
