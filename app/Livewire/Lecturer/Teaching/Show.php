<?php

namespace App\Livewire\Lecturer\Teaching;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Enums\ClassJoinRequestStatus;
use App\Models\ClassJoinRequest;
use App\Models\TeachingAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('layouts.app')]
class Show extends Component
{
    use Interactions;

    public TeachingAssignment $assignment;

    public function mount(TeachingAssignment $assignment): void
    {
        abort_unless(auth()->user()->hasPermission('view-assigned-classes'), 403);
        abort_unless($assignment->isOwnedBy(auth()->user()), 403);

        $this->assignment = $assignment->load(['schoolClass.students.roles', 'subject']);
    }

    public function exams()
    {
        return $this->assignment
            ->exams()
            ->withCount([
                'questions',
                'attempts',
                'attempts as pending_marking_count' => fn ($query) => $query->where('status', ExamAttemptStatus::Submitted->value),
            ])
            ->latest()
            ->get();
    }

    public function students()
    {
        return $this->assignment->schoolClass
            ->students()
            ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
            ->orderBy('name')
            ->get();
    }

    public function joinRequests()
    {
        return ClassJoinRequest::query()
            ->with(['student.schoolClass'])
            ->where('school_class_id', $this->assignment->school_class_id)
            ->where('status', ClassJoinRequestStatus::Pending)
            ->latest()
            ->get();
    }

    public function askApproveJoinRequest(int $id): void
    {
        $request = $this->findPendingJoinRequest($id);

        $this->dialog()
            ->question('Approve class request?', "{$request->student->name} will be enrolled into {$this->assignment->schoolClass->name}.")
            ->confirm('Approve', 'approveJoinRequest', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function approveJoinRequest(int $id): void
    {
        $request = $this->findPendingJoinRequest($id);

        $request->student->update(['school_class_id' => $request->school_class_id]);
        $request->update([
            'status' => ClassJoinRequestStatus::Approved,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->assignment->load('schoolClass.students.roles');
        $this->toast()->success('Request approved.', "{$request->student->name} is now in {$this->assignment->schoolClass->name}.")->send();
    }

    public function askRejectJoinRequest(int $id): void
    {
        $request = $this->findPendingJoinRequest($id);

        $this->dialog()
            ->question('Reject class request?', "{$request->student->name}'s request will be declined.")
            ->confirm('Reject', 'rejectJoinRequest', $id)
            ->cancel('Cancel')
            ->send();
    }

    public function rejectJoinRequest(int $id): void
    {
        $request = $this->findPendingJoinRequest($id);

        $request->update([
            'status' => ClassJoinRequestStatus::Rejected,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->toast()->success('Request rejected.')->send();
    }

    public function summary(): array
    {
        $exams = $this->exams();

        return [
            'students' => $this->students()->count(),
            'exams' => $exams->count(),
            'published' => $exams->where('status', ExamStatus::Published)->count(),
            'pendingMarking' => $exams->sum('pending_marking_count'),
            'joinRequests' => $this->joinRequests()->count(),
        ];
    }

    private function findPendingJoinRequest(int $id): ClassJoinRequest
    {
        abort_unless($this->assignment->isOwnedBy(auth()->user()), 403);

        return ClassJoinRequest::query()
            ->with('student')
            ->where('school_class_id', $this->assignment->school_class_id)
            ->where('status', ClassJoinRequestStatus::Pending)
            ->findOrFail($id);
    }

    public function render(): View
    {
        return view('livewire.lecturer.teaching.show');
    }
}
