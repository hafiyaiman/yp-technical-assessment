<?php

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\TeachingAssignment;
use App\Services\Exams\ExamPublicationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.app')] class extends Component {
    use Interactions;
    use WithPagination;

    #[Url]
    public string $assignmentFilter = '';

    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('manage-exams'), 403);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAssignmentFilter(): void
    {
        $this->resetPage();
    }

    public function askPublish(int $id): void
    {
        $exam = $this->findOwnedExam($id);

        $this->dialog()
            ->question('Publish exam?', "Students in {$exam->schoolClass->name} will be able to access {$exam->title}.")
            ->confirm('Publish', 'confirmPublish', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmPublish(int $id, ExamPublicationService $publisher): void
    {
        $publisher->publish($this->findOwnedExam($id)->load(['questions.options', 'schoolClass.subjects', 'teachingAssignment']));

        $this->toast()->success('Exam published.')->send();
    }

    public function askClose(int $id): void
    {
        $exam = $this->findOwnedExam($id);

        $this->dialog()
            ->question('Close exam?', "Students will no longer be able to start {$exam->title}.")
            ->confirm('Close exam', 'confirmClose', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmClose(int $id, ExamPublicationService $publisher): void
    {
        $publisher->close($this->findOwnedExam($id));

        $this->toast()->success('Exam closed.')->send();
    }

    public function askDelete(int $id): void
    {
        $exam = $this->findOwnedExam($id);

        $this->dialog()
            ->question('Delete exam?', "This will permanently delete {$exam->title}, including questions and submissions.")
            ->confirm('Yes, delete', 'confirmDelete', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        $this->findOwnedExam($id)->delete();

        $this->toast()->success('Exam deleted.')->send();
    }

    public function exams()
    {
        return Exam::query()
            ->with(['schoolClass', 'subject', 'teachingAssignment'])
            ->withCount(['questions', 'attempts'])
            ->assignedTo(auth()->user())
            ->when($this->statusFilter !== '', fn($query) => $query->where('status', $this->statusFilter))
            ->when($this->assignmentFilter !== '', fn($query) => $query->where('teaching_assignment_id', $this->assignmentFilter))
            ->latest()
            ->paginate(10);
    }

    public function assignments()
    {
        return TeachingAssignment::query()
            ->with(['schoolClass', 'subject'])
            ->where('lecturer_id', auth()->id())
            ->latest()
            ->get();
    }

    private function findOwnedExam(int $id): Exam
    {
        $exam = Exam::query()
            ->with(['schoolClass', 'subject', 'teachingAssignment'])
            ->assignedTo(auth()->user())
            ->findOrFail($id);

        abort_unless(auth()->user()->can('update', $exam), 403);

        return $exam;
    }

    public function headers(): array
    {
        return [['index' => 'title', 'label' => 'Exam'], ['index' => 'assignment', 'label' => 'Class / Subject', 'sortable' => false], ['index' => 'status', 'label' => 'Status'], ['index' => 'questions_count', 'label' => 'Questions'], ['index' => 'attempts_count', 'label' => 'Submissions'], ['index' => 'action', 'label' => 'Actions', 'sortable' => false, 'align' => 'center']];
    }
}; ?>

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
                @foreach (ExamStatus::cases() as $status)
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
                <x-badge :text="str($row->status->value)->headline()" :color="$row->status === ExamStatus::Published
                    ? 'green'
                    : ($row->status === ExamStatus::Closed
                        ? 'red'
                        : 'gray')" light />
            @endinteract

            @interact('column_action', $row)
                <div class="flex justify-center">
                    <x-dropdown icon="ellipsis-vertical" position="bottom-end">
                        <x-dropdown.items text="Builder" icon="pencil-square" :href="route('lecturer.exams.edit', $row)" navigate />
                        <x-dropdown.items text="Submissions" icon="clipboard-document-check" :href="route('lecturer.exams.submissions', $row)" navigate />

                        @if ($row->status !== ExamStatus::Published)
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
