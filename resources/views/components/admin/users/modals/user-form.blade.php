@props([
    'editingId' => null,
    'role' => 'student',
    'classes' => collect(),
    'roleOptions' => [],
])

@php
    $classOptions = collect([['label' => 'No class yet', 'value' => '']])
        ->merge($classes->map(fn ($class) => ['label' => $class->name, 'value' => (string) $class->id]))
        ->all();
@endphp

<form wire:submit="save" class="space-y-4">
    <x-input wire:model="name" label="Name" placeholder="Amina Rahman" />
    <x-input wire:model="email" label="Email" placeholder="user@example.com" />

    <x-select.styled wire:model.live="role" label="Role" :options="$roleOptions"
        select="label:label|value:value" />

    @if ($role === 'student')
        <x-select.styled wire:model="school_class_id" label="Class" :options="$classOptions"
            select="label:label|value:value" searchable />
    @elseif ($role === 'lecturer' && $editingId)
        <x-alert color="blue" light>
            Lecturer class and subject access is edited from the row action menu: Manage teaching classes.
        </x-alert>
    @endif

    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
        <x-button type="button" text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />
        <x-button type="submit" text="{{ $editingId ? 'Update User' : 'Create User' }}" icon="check" loading="save" />
    </div>
</form>
