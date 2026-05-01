<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Services\Exams\OpenTextGradingService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
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

        session()->flash('status', __('Answer graded.'));
    }

    public function attempts()
    {
        return $this->exam
            ->attempts()
            ->with(['student', 'answers.question', 'answers.selectedOption'])
            ->latest()
            ->get();
    }
}; ?>

<div class="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-950">Submissions</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ $exam->title }} · {{ $exam->schoolClass->name }} · {{ $exam->subject->name }}</p>
        </div>
        <x-button text="Back to Exams" icon="arrow-left" flat :href="route('lecturer.exams.index')" navigate />
    </div>

    <x-auth-session-status :status="session('status')" />

    <div class="space-y-4">
        @forelse ($this->attempts() as $attempt)
            <x-card>
                <div class="flex flex-col gap-3 border-b border-zinc-100 pb-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-semibold text-zinc-950">{{ $attempt->student->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $attempt->student->email }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :text="str($attempt->status->value)->headline()" :color="$attempt->status === ExamAttemptStatus::Graded ? 'green' : ($attempt->status === ExamAttemptStatus::Expired ? 'red' : 'yellow')" light />
                        <x-badge text="{{ $attempt->score }} / {{ $attempt->max_score }} points" color="gray" light />
                    </div>
                </div>

                <div class="mt-4 space-y-4">
                    @foreach ($attempt->answers as $answer)
                        <div class="rounded-lg border border-zinc-200 p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-950">{{ $answer->question->prompt }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $answer->question->points }} point question</p>
                                </div>
                                <x-badge text="{{ $answer->points_awarded }} pts" color="gray" light />
                            </div>

                            @if ($answer->question->type === QuestionType::MultipleChoice)
                                <p class="mt-3 rounded-md bg-zinc-50 px-3 py-2 text-sm text-zinc-700">
                                    Selected: {{ $answer->selectedOption?->text ?? 'No answer' }}
                                </p>
                            @else
                                <p class="mt-3 rounded-md bg-zinc-50 px-3 py-2 text-sm leading-6 text-zinc-700">
                                    {{ $answer->open_text_answer ?: 'No written answer.' }}
                                </p>

                                <div class="mt-4 grid gap-3 md:grid-cols-[160px_minmax(0,1fr)_auto] md:items-end">
                                    <x-number wire:model="points.{{ $answer->id }}" label="Points" :min="0" :max="$answer->question->points" />
                                    <x-input wire:model="feedback.{{ $answer->id }}" label="Feedback" placeholder="Short feedback for the student" />
                                    <x-button text="Save Grade" wire:click="grade({{ $answer->id }})" />
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-card>
        @empty
            <x-card>
                <div class="py-10 text-center">
                    <p class="font-semibold text-zinc-950">No submissions yet</p>
                    <p class="mt-1 text-sm text-zinc-500">Student attempts will appear here after they submit.</p>
                </div>
            </x-card>
        @endforelse
    </div>
</div>
