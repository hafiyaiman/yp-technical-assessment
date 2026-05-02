<?php

namespace App\Livewire\Lecturer\Teaching;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Models\TeachingAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
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

    public function summary(): array
    {
        $exams = $this->exams();

        return [
            'students' => $this->students()->count(),
            'exams' => $exams->count(),
            'published' => $exams->where('status', ExamStatus::Published)->count(),
            'pendingMarking' => $exams->sum('pending_marking_count'),
        ];
    }

    public function render(): View
    {
        return view('livewire.lecturer.teaching.show');
    }
}
