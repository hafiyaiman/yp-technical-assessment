<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.student')] class extends Component
{
    public ExamAttempt $attempt;

    public function mount(ExamAttempt $attempt): void
    {
        abort_unless(auth()->user()->can('view', $attempt), 403);

        $this->attempt = $attempt->load(['exam.subject', 'exam.questions', 'answers.question', 'answers.selectedOption']);
    }

    public function answerFor(int $questionId)
    {
        return $this->attempt->answers->firstWhere('question_id', $questionId);
    }
}; ?>

<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-zinc-500">{{ $attempt->exam->subject->name }}</p>
            <h1 class="mt-1 text-3xl font-semibold text-zinc-950">{{ $attempt->exam->title }}</h1>
        </div>
        <x-button text="Back to Results" flat :href="route('student.results.index')" navigate />
    </div>

    <x-card>
        <div class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-zinc-50 p-4">
                <p class="text-xs font-medium uppercase text-zinc-500">Status</p>
                <p class="mt-1 font-semibold text-zinc-950">{{ str($attempt->status->value)->headline() }}</p>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4">
                <p class="text-xs font-medium uppercase text-zinc-500">Score</p>
                <p class="mt-1 font-semibold text-zinc-950">
                    {{ $attempt->status === ExamAttemptStatus::Graded ? "{$attempt->score} / {$attempt->max_score}" : 'Pending' }}
                </p>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4">
                <p class="text-xs font-medium uppercase text-zinc-500">Submitted</p>
                <p class="mt-1 font-semibold text-zinc-950">{{ $attempt->submitted_at?->format('M j, Y') ?? 'Not submitted' }}</p>
            </div>
        </div>

        <div class="space-y-4">
            @foreach ($attempt->exam->questions as $question)
                @php($answer = $this->answerFor($question->id))
                <div class="rounded-xl border border-zinc-200 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-zinc-950">{{ $loop->iteration }}. {{ $question->prompt }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $question->points }} point{{ $question->points === 1 ? '' : 's' }}</p>
                        </div>
                        <x-badge text="{{ $answer?->points_awarded ?? 0 }} pts" color="gray" light />
                    </div>

                    <p class="mt-3 rounded-lg bg-zinc-50 px-3 py-2 text-sm leading-6 text-zinc-700">
                        @if ($question->type === QuestionType::MultipleChoice)
                            {{ $answer?->selectedOption?->text ?? 'No answer selected.' }}
                        @else
                            {{ $answer?->open_text_answer ?: 'No written answer.' }}
                        @endif
                    </p>

                    @if ($answer?->feedback)
                        <p class="mt-3 rounded-lg border border-zinc-200 px-3 py-2 text-sm leading-6 text-zinc-600">
                            Feedback: {{ $answer->feedback }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-card>
</div>
