@props([
    'subjects' => collect(),
])

<x-step.items step="2" title="Subjects" description="Attach subjects">
    <div class="pt-2">
        <p class="text-sm font-semibold text-zinc-900 dark:text-white">Subjects</p>

        <div class="mt-2 grid max-h-72 gap-2 overflow-y-auto rounded-md border border-zinc-200 p-2 dark:border-dark-600">
            @forelse ($subjects as $subject)
                <div class="rounded-md border border-zinc-200 px-3 py-2 dark:border-dark-600">
                    <x-checkbox wire:model="subjectIds" :value="$subject->id" :label="$subject->name" />
                </div>
            @empty
                <p class="px-2 py-3 text-sm text-zinc-500 dark:text-dark-300">
                    Create subjects before assigning them.
                </p>
            @endforelse
        </div>
    </div>
</x-step.items>
