@props([
    'name' => '',
    'email' => '',
    'classes' => collect(),
])

@php
    $classOptions = collect([['label' => 'No class yet', 'value' => '']])
        ->merge($classes->map(fn ($class) => ['label' => $class->name, 'value' => (string) $class->id]))
        ->all();
@endphp

<form wire:submit="saveClassAssignment" class="space-y-4">
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-dark-600 dark:bg-dark-700">
        <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $name }}</p>
        <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $email }}</p>
    </div>

    <x-select.styled wire:model="school_class_id" label="Class" :options="$classOptions"
        select="label:label|value:value" searchable />

    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
        <x-button type="button" text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />
        <x-button type="submit" text="Save Assignment" icon="check" loading="saveClassAssignment" />
    </div>
</form>
