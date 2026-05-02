<?php

namespace App\Livewire\Lecturer\Exams;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamStatus;
use App\Models\Exam;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        $exam->load(['teachingAssignment', 'schoolClass.students', 'subject', 'questions']);
        abort_unless(auth()->user()->can('manageSubmissions', $exam), 403);

        $this->exam = $exam;
    }

    public function summary(): array
    {
        $students = $this->exam->schoolClass->students()->whereHas('roles', fn ($query) => $query->where('slug', 'student'))->count();
        $attempts = $this->exam->attempts()->get();

        return [
            'students' => $students,
            'questions' => $this->exam->questions()->count(),
            'attempts' => $attempts->count(),
            'submitted' => $attempts->whereIn('status', [
                ExamAttemptStatus::Submitted,
                ExamAttemptStatus::Graded,
                ExamAttemptStatus::Expired,
            ])->count(),
            'graded' => $attempts->where('status', ExamAttemptStatus::Graded)->count(),
            'pendingMarking' => $attempts->where('status', ExamAttemptStatus::Submitted)->count(),
        ];
    }

    public function statusColor(): string
    {
        return match ($this->exam->status) {
            ExamStatus::Published => 'green',
            ExamStatus::Closed => 'red',
            default => 'gray',
        };
    }

    public function render(): View
    {
        return view('livewire.lecturer.exams.show');
    }
}
