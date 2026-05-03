<?php

namespace App\Livewire\Lecturer\Exams;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Services\AuditLogger;
use App\Services\Exams\OpenTextGradingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

#[Layout('layouts.app')]
class Submissions extends Component
{
    use Interactions;

    public Exam $exam;

    public array $points = [];

    public array $feedback = [];

    public function mount(Exam $exam): void
    {
        $exam->load(['teachingAssignment', 'schoolClass', 'subject']);
        abort_unless(auth()->user()->can('manageSubmissions', $exam), 403);

        $this->exam = $exam;

        foreach ($this->attempts() as $attempt) {
            foreach ($attempt->answers as $answer) {
                $this->points[$answer->id] = $answer->points_awarded;
                $this->feedback[$answer->id] = (string) $answer->feedback;
            }
        }
    }

    public function grade(int $answerId, OpenTextGradingService $grader): void
    {
        $answer = ExamAnswer::query()
            ->with(['question', 'attempt.exam.teachingAssignment', 'attempt.exam.questions', 'attempt.answers.question'])
            ->findOrFail($answerId);

        abort_unless($answer->attempt->exam_id === $this->exam->id, 404);
        abort_unless(auth()->user()->can('grade', $answer->attempt), 403);

        $grader->grade(
            $answer,
            (int) ($this->points[$answerId] ?? 0),
            $this->feedback[$answerId] ?? null,
        );

        app(AuditLogger::class)->record(
            'exam_answer.graded',
            'Graded '.$answer->attempt->student->name.' answer for '.$this->exam->title.'.',
            $this->exam,
            ['attempt_id' => $answer->attempt_id, 'answer_id' => $answer->id],
        );

        $this->toast()->success('Answer graded.')->send();
    }

    public function attempts()
    {
        return $this->exam
            ->attempts()
            ->with(['student', 'answers.question', 'answers.selectedOption'])
            ->latest()
            ->get();
    }

    public function render(): View
    {
        return view('livewire.lecturer.exams.submissions');
    }
}
