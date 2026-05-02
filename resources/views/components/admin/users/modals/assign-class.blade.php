@props([
    'name' => '',
    'email' => '',
    'classes' => collect(),
])

<form wire:submit="saveClassAssignment" class="space-y-4">
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-dark-600 dark:bg-dark-700">
        <p class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $name }}</p>
        <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $email }}</p>
    </div>

    <x-select.styled wire:model="school_class_id" label="Class">
        <option value="">No class yet</option>
        @foreach ($classes as $class)
            <option value="{{ $class->id }}">{{ $class->name }}</option>
        @endforeach
    </x-select.styled>

    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <x-button type="button" text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />
        <x-button type="submit" text="Save Assignment" icon="check" />
    </div>
</form>
