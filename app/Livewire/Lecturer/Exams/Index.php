<?php

namespace App\Livewire\Lecturer\Exams;

use App\Models\Exam;
use App\Models\TeachingAssignment;
use App\Services\AuditLogger;
use App\Services\Exams\ExamPublicationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use TallStackUi\Traits\Interactions;

#[Layout('layouts.app')]
class Index extends Component
{
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
        $exam = $this->findOwnedExam($id);

        app(AuditLogger::class)->record('exam.deleted', 'Deleted exam '.$exam->title.'.', $exam);

        $exam->delete();

        $this->toast()->success('Exam deleted.')->send();
    }

    public function exams()
    {
        return Exam::query()
            ->with(['schoolClass', 'subject', 'teachingAssignment'])
            ->withCount(['questions', 'attempts'])
            ->assignedTo(auth()->user())
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->assignmentFilter !== '', fn ($query) => $query->where('teaching_assignment_id', $this->assignmentFilter))
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

    public function render(): View
    {
        return view('livewire.lecturer.exams.index');
    }
}
