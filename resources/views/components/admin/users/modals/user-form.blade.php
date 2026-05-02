@props([
    'editingId' => null,
    'role' => 'student',
    'classes' => collect(),
])

<form wire:submit="save" class="space-y-4">
    <x-input wire:model="name" label="Name" placeholder="Amina Rahman" />
    <x-input wire:model="email" label="Email" placeholder="user@example.com" />

    <x-select.styled wire:model.live="role" label="Role">
        <option value="system-admin">System Admin</option>
        <option value="lecturer">Lecturer</option>
        <option value="student">Student</option>
    </x-select.styled>

    @if ($role === 'student')
        <x-select.styled wire:model="school_class_id" label="Class">
            <option value="">No class yet</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
            @endforeach
        </x-select.styled>
    @elseif ($role === 'lecturer' && $editingId)
        <x-alert color="blue" light>
            Lecturer class and subject access is edited from the row action menu: Manage teaching classes.
        </x-alert>
    @endif

    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <x-button type="button" text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />
        <x-button type="submit" text="{{ $editingId ? 'Update User' : 'Create User' }}" icon="check" />
    </div>
</form>
