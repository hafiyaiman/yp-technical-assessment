<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Teaching Assignments</h1>
            <p class="mt-1 text-sm text-zinc-500">Assign lecturers to the class-subject pairs they teach.</p>
        </div>
        <x-button text="Create Assignment" icon="plus" wire:click="create" loading="create" />
    </div>

    <x-card>
        <x-table :headers="$this->headers()" :rows="$this->assignments()" striped paginate>
            @interact('column_lecturer', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->lecturer->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->lecturer->email }}</p>
                </div>
            @endinteract

            @interact('column_class_subject', $row)
                <div>
                    <p class="text-zinc-800">{{ $row->schoolClass->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->subject->name }}</p>
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
                No teaching assignments yet.
            </x-slot:empty>
        </x-table>
    </x-card>

    <x-modal wire title="{{ $editingId ? 'Edit Assignment' : 'Create Assignment' }}" size="lg" center persistent>
        <form wire:submit="save" class="space-y-4">
            <x-select.styled wire:model="lecturer_id" label="Lecturer">
                <option value="">Select lecturer</option>
                @foreach ($this->lecturers() as $lecturer)
                    <option value="{{ $lecturer->id }}">{{ $lecturer->name }}</option>
                @endforeach
            </x-select.styled>

            <x-select.styled wire:model.live="school_class_id" label="Class">
                <option value="">Select class</option>
                @foreach ($this->classes() as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                @endforeach
            </x-select.styled>

            <x-select.styled wire:model="subject_id" label="Subject">
                <option value="">Select subject</option>
                @foreach ($this->subjects() as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                @endforeach
            </x-select.styled>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-between">
                <x-button type="button" text="Cancel" color="gray" outline
                    x-on:click="$tsui.close.modal('modal')" />
                <x-button type="submit" text="{{ $editingId ? 'Update Assignment' : 'Create Assignment' }}"
                    icon="check" loading="save" />
            </div>
        </form>
    </x-modal>
</div>
