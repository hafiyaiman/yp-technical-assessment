<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Subjects</h1>
            <p class="mt-1 text-sm text-zinc-500">Create reusable subjects that can be attached to classes.</p>
        </div>
        <x-button text="Create Subject" icon="plus" wire:click="create" loading="create" />
    </div>

    <x-card>
        <div class="mb-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
            <x-input wire:model.live.debounce.500ms="search" label="Search" icon="magnifying-glass"
                placeholder="Search subject name or code" />
            <x-select.styled wire:model.live="classFilters" label="Classes" :options="$this->classOptions()"
                select="label:label|value:value" searchable multiple />
        </div>

        <x-table :headers="$this->headers()" :rows="$this->subjects()" striped paginate>
            @interact('column_name', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->code }}</p>
                </div>
            @endinteract

            @interact('column_action', $row)
                <div class="flex justify-center">
                    <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                        <x-dropdown.items text="Edit" icon="pencil-square" wire:click="edit({{ $row->id }})" />
                        <x-dropdown.items text="Delete" icon="trash" separator
                            wire:click="askDelete({{ $row->id }})" />
                    </x-dropdown>
                </div>
            @endinteract

            <x-slot:empty>
                No subjects yet.
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire title="{{ $editingId ? 'Edit Subject' : 'Create Subject' }}" size="lg" center>
        <div class="space-y-1">
            <p class="text-sm text-zinc-500">Subjects are reusable and can be attached to one or more classes.</p>
        </div>

        <div class="mt-5">
            <form wire:submit="save" class="space-y-4">
                <x-input wire:model="name" label="Subject name" placeholder="Mathematics" />
                <x-input wire:model="code" label="Subject code" placeholder="MATH" />
                <x-textarea wire:model="description" label="Description" />

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
                    <x-button type="button" text="Cancel" color="gray" outline
                        x-on:click="$tsui.close.modal('modal')" />
                    <x-button type="submit" text="{{ $editingId ? 'Update Subject' : 'Create Subject' }}"
                        icon="check" loading="save" />
                </div>
            </form>
        </div>
    </x-modal>
</div>
