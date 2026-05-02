<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Exams</h1>
            <p class="mt-1 text-sm text-zinc-500">Create, publish, and monitor exams for your assigned classes.</p>
        </div>
        <x-button text="Create from My Classes" icon="plus" :href="route('lecturer.teaching.index')" navigate />
    </div>

    <x-card>
        <div class="mb-5 grid gap-3 md:grid-cols-3">
            <x-select.styled wire:model.live="statusFilter" label="Status">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ExamStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ str($status->value)->headline() }}</option>
                @endforeach
            </x-select.styled>

            <x-select.styled wire:model.live="assignmentFilter" label="Class / Subject">
                <option value="">All assignments</option>
                @foreach ($this->assignments() as $assignment)
                    <option value="{{ $assignment->id }}">{{ $assignment->schoolClass->name }} /
                        {{ $assignment->subject->name }}</option>
                @endforeach
            </x-select.styled>
        </div>

        <x-table :headers="$this->headers()" :rows="$this->exams()" striped paginate>
            @interact('column_title', $row)
                <div>
                    <p class="font-medium text-zinc-950">{{ $row->title }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->duration_minutes }} minutes</p>
                </div>
            @endinteract

            @interact('column_assignment', $row)
                <div>
                    <p class="text-zinc-800">{{ $row->schoolClass->name }}</p>
                    <p class="text-xs text-zinc-500">{{ $row->subject->name }}</p>
                </div>
            @endinteract

            @interact('column_status', $row)
                <x-badge :text="str($row->status->value)->headline()" :color="$row->status === \App\Enums\ExamStatus::Published
                    ? 'green'
                    : ($row->status === \App\Enums\ExamStatus::Closed
                        ? 'red'
                        : 'gray')" light />
            @endinteract

            @interact('column_action', $row)
                <div class="flex justify-center">
                    <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                        <x-dropdown.items text="Builder" icon="pencil-square" :href="route('lecturer.exams.edit', $row)" navigate />
                        <x-dropdown.items text="Submissions" icon="clipboard-document-check" :href="route('lecturer.exams.submissions', $row)" navigate />

                        @if ($row->status !== \App\Enums\ExamStatus::Published)
                            <x-dropdown.items text="Publish" icon="rocket-launch"
                                wire:click="askPublish({{ $row->id }})" />
                        @else
                            <x-dropdown.items text="Close" icon="lock-closed"
                                wire:click="askClose({{ $row->id }})" />
                        @endif

                        <x-dropdown.items text="Delete" icon="trash" separator
                            wire:click="askDelete({{ $row->id }})" />
                    </x-dropdown>
                </div>
            @endinteract

            <x-slot:empty>
                No exams yet. Create your first exam to begin.
            </x-slot:empty>
        </x-table>
    </x-card>
</div>
