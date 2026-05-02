@props([
    'name' => '',
    'email' => '',
    'teachingAssignmentKeys' => [],
    'teachingGroups' => collect(),
    'selectedTeachingOptions' => collect(),
])

<form wire:submit="saveTeachingAssignments" class="space-y-5">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-dark-600 dark:bg-dark-700">
            <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $name }}</p>
            <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $email }}</p>
            <p class="mt-3 text-sm text-zinc-600 dark:text-dark-200">
                Choose the classes this lecturer teaches, then tick the subjects handled in each class.
            </p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white px-4 py-3 dark:border-dark-600 dark:bg-dark-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-dark-300">
                Selected teaching load
            </p>
            <p class="mt-1 text-2xl font-semibold text-zinc-950 dark:text-white">
                {{ count($teachingAssignmentKeys) }}
            </p>
            <div class="mt-3 flex flex-wrap gap-1">
                @forelse ($selectedTeachingOptions->take(4) as $option)
                    <x-badge :text="$option['label']" color="blue" light />
                @empty
                    <span class="text-sm text-zinc-500 dark:text-dark-300">No classes selected.</span>
                @endforelse

                @if ($selectedTeachingOptions->count() > 4)
                    <x-badge :text="'+' . ($selectedTeachingOptions->count() - 4) . ' more'" color="gray" light />
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-end">
        <x-input wire:model.live.debounce.300ms="teachingSearch" label="Find class or subject" icon="magnifying-glass"
            placeholder="Search Class 4A, Mathematics..." />
        <x-button type="button" text="Select Visible" icon="check-circle" color="gray" outline
            wire:click="selectVisibleTeachingAssignments" loading="selectVisibleTeachingAssignments" />
        <x-button type="button" text="Clear" icon="x-mark" color="gray" flat
            wire:click="clearTeachingAssignments" loading="clearTeachingAssignments" />
    </div>

    <x-input-error :messages="$errors->get('teachingAssignmentKeys')" />

    <div class="grid max-h-[28rem] gap-3 overflow-y-auto pr-1 lg:grid-cols-2">
        @forelse ($teachingGroups as $group)
            @php
                $selectedInClass = collect($group['subjects'])
                    ->whereIn('value', collect($teachingAssignmentKeys)->map(fn($key) => (string) $key)->all())
                    ->count();
            @endphp

            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-dark-600 dark:bg-dark-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $group['name'] }}</p>
                        <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $group['code'] }}</p>
                    </div>
                    <x-badge :text="$selectedInClass . ' / ' . count($group['subjects'])" color="gray" light />
                </div>

                <div class="mt-4 grid gap-2">
                    @foreach ($group['subjects'] as $subject)
                        <div class="rounded-md border border-zinc-200 px-3 py-2 dark:border-dark-600">
                            <x-checkbox wire:model="teachingAssignmentKeys" :value="$subject['value']" :label="$subject['name']" />
                            <p class="ml-7 mt-0.5 text-xs text-zinc-500 dark:text-dark-300">{{ $subject['code'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div
                class="rounded-lg border border-dashed border-zinc-300 px-4 py-8 text-center dark:border-dark-600 lg:col-span-2">
                <p class="font-medium text-zinc-950 dark:text-white">No class-subject pairs found</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                    Attach subjects to classes first, or adjust your search.
                </p>
            </div>
        @endforelse
    </div>

    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
        <x-button type="button" text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />
        <x-button type="submit" text="Save Teaching" icon="check" loading="saveTeachingAssignments" />
    </div>
</form>
