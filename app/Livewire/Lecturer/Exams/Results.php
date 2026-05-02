<?php

namespace App\Livewire\Lecturer\Exams;

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Results extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        $exam->load(['teachingAssignment', 'schoolClass', 'subject']);
        abort_unless(auth()->user()->can('manageSubmissions', $exam), 403);

        $this->exam = $exam;
    }

    public function attempts()
    {
        return $this->exam
            ->attempts()
            ->with('student')
            ->latest()
            ->get();
    }

    public function summary(): array
    {
        $attempts = $this->attempts();
        $gradedAttempts = $attempts->where('status', ExamAttemptStatus::Graded);
        $scores = $gradedAttempts->pluck('score');

        return [
            'completion' => $attempts->count(),
            'graded' => $gradedAttempts->count(),
            'pending' => $attempts->where('status', ExamAttemptStatus::Submitted)->count(),
            'average' => $scores->isNotEmpty() ? round($scores->average(), 1) : 0,
            'highest' => $scores->max() ?? 0,
            'lowest' => $scores->min() ?? 0,
        ];
    }

    public function render(): View
    {
        return view('livewire.lecturer.exams.results');
    }
}
