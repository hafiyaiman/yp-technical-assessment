<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Exams</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Choose an exam to manage builder, submissions,
                marking, results, and activity.</p>
        </div>
        <x-button text="Create Exam" icon="plus" wire:click="openCreateModal" loading="openCreateModal" />
    </div>

    <x-card>
        <div class="mb-5 grid gap-3 md:grid-cols-3">
            <x-input wire:model.live.debounce.400ms="search" label="Search" icon="magnifying-glass"
                placeholder="Exam title" />

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

            @interact('column_pending_marking_count', $row)
                <x-badge :text="$row->pending_marking_count . ' pending'" :color="$row->pending_marking_count > 0 ? 'yellow' : 'gray'" light />
            @endinteract

            @interact('column_action', $row)
                <div class="flex justify-center">
                    <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                        <x-dropdown.items text="View Overview" icon="document-magnifying-glass" :href="route('lecturer.exams.show', $row)"
                            navigate />
                        <x-dropdown.items text="Builder" icon="pencil-square" :href="route('lecturer.exams.edit', $row)" navigate />
                        <x-dropdown.items text="Submissions" icon="clipboard-document-check" :href="route('lecturer.exams.submissions', $row)" navigate />
                        <x-dropdown.items text="Results" icon="chart-bar" :href="route('lecturer.exams.results', $row)" navigate />

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

    <x-modal wire title="Choose Class & Subject" size="5xl" center scrollable persistent>
        <div class="space-y-5">
            <div>
                <p class="text-sm text-zinc-600 dark:text-dark-300">
                    Pick the class and subject for this exam. The builder will create the exam under that teaching
                    context.
                </p>
            </div>

            <x-input wire:model.live.debounce.400ms="createSearch" label="Search" icon="magnifying-glass"
                placeholder="Class 4A, Mathematics..." />

            <div class="grid max-h-[55vh] gap-4 overflow-y-auto pr-1 md:grid-cols-2">
                @forelse ($this->createAssignments() as $assignment)
                    <div
                        class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-dark-600 dark:bg-dark-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm text-zinc-500 dark:text-dark-300">
                                    {{ $assignment->subject->name }} / {{ $assignment->subject->code }}
                                </p>
                                <h3 class="mt-1 truncate text-lg font-semibold text-zinc-950 dark:text-white">
                                    {{ $assignment->schoolClass->name }}
                                </h3>
                            </div>
                            <x-badge text="{{ $assignment->exams_count }} exams" color="gray" light />
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-md border border-zinc-200 p-3 dark:border-dark-600">
                                <p class="text-zinc-500 dark:text-dark-300">Students</p>
                                <p class="mt-1 text-xl font-semibold text-zinc-950 dark:text-white">
                                    {{ $assignment->schoolClass->students->count() }}
                                </p>
                            </div>
                            <div class="rounded-md border border-zinc-200 p-3 dark:border-dark-600">
                                <p class="text-zinc-500 dark:text-dark-300">Class Code</p>
                                <p class="mt-1 font-semibold text-zinc-950 dark:text-white">
                                    {{ $assignment->schoolClass->code }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <x-button text="Select this class" :href="route('lecturer.teaching.exams.create', $assignment)" navigate class="flex-1" />
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-dark-600 md:col-span-2">
                        <p class="font-semibold text-zinc-950 dark:text-white">No class-subject assignment found</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                            Ask a system admin to assign your teaching classes before creating exams.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
        <x-slot:footer class="flex justify-between border-t border-zinc-200 pt-4 dark:border-dark-600">
            <x-button text="Cancel" color="gray" outline x-on:click="$tsui.close.modal('modal')" />
        </x-slot:footer>
    </x-modal>
</div>
