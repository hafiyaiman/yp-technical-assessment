@props([
    'headers' => [],
    'rows',
])

<x-table :headers="$headers" :rows="$rows" striped paginate>
    @interact('column_name', $row)
        <div>
            <p class="font-medium text-zinc-950 dark:text-white">{{ $row->name }}</p>
            <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $row->email }}</p>
        </div>
    @endinteract

    @interact('column_roles', $row)
        <div class="flex flex-wrap gap-1">
            @foreach ($row->roles as $role)
                <x-badge :text="$role->name" color="gray" light />
            @endforeach
        </div>
    @endinteract

    @interact('column_class', $row)
        @if ($row->hasRole('lecturer'))
            <div class="flex max-w-xl flex-wrap gap-1">
                @forelse ($row->teachingAssignments as $assignment)
                    <x-badge :text="$assignment->schoolClass->name . ' / ' . $assignment->subject->name" color="gray" light />
                @empty
                    <span class="text-sm text-zinc-600 dark:text-dark-300">Not assigned</span>
                @endforelse
            </div>
        @else
            <span class="text-sm text-zinc-600 dark:text-dark-300">
                {{ $row->schoolClass?->name ?? 'Not assigned' }}
            </span>
        @endif
    @endinteract

    @interact('column_action', $row)
        <div class="flex justify-center">
            <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                <x-dropdown.items text="Edit" icon="pencil-square" wire:click="edit({{ $row->id }})" />

                @if ($row->hasRole('student'))
                    <x-dropdown.items :text="$row->school_class_id ? 'Change class' : 'Assign class'"
                        icon="building-library" wire:click="assignClass({{ $row->id }})" />
                @endif

                @if ($row->hasRole('lecturer'))
                    <x-dropdown.items text="Manage teaching classes" icon="clipboard-document-list"
                        wire:click="manageTeaching({{ $row->id }})" />
                @endif

                <x-dropdown.items text="Delete" icon="trash" separator wire:click="askDelete({{ $row->id }})" />
            </x-dropdown>
        </div>
    @endinteract

    <x-slot:empty>
        No users found.
    </x-slot:empty>
</x-table>
