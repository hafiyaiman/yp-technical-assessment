@props([
    'headers' => [],
    'rows',
])

<x-table :headers="$headers" :rows="$rows" striped paginate>
    @interact('column_name', $row)
        <div>
            <p class="font-medium text-zinc-950 dark:text-white">{{ $row->name }}</p>
            <p class="text-xs text-zinc-500 dark:text-dark-300">{{ $row->code }}</p>
        </div>
    @endinteract

    @interact('column_subjects', $row)
        <div class="flex flex-wrap gap-1">
            @foreach ($row->subjects as $subject)
                <x-badge :text="$subject->name" color="gray" light />
            @endforeach
        </div>
    @endinteract

    @interact('column_action', $row)
        <div class="flex justify-center">
            <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                <x-dropdown.items text="Edit" icon="pencil-square" wire:click="edit({{ $row->id }})" />
                <x-dropdown.items text="Delete" icon="trash" separator wire:click="askDelete({{ $row->id }})" />
            </x-dropdown>
        </div>
    @endinteract

    <x-slot:empty>
        No classes yet.
    </x-slot:empty>
</x-table>
