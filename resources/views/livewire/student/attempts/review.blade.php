<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamAttemptService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use TallStackUi\Traits\Interactions;

new #[Layout('layouts.student')] class extends Component
{
    use Interactions;

    public ExamAttempt $attempt;

    public function mount(ExamAttempt $attempt): void
    {
        abort_unless(auth()->user()->can('submit', $attempt), 403);

        $this->attempt = $attempt->load(['exam.questions.options', 'answers.selectedOption']);

        if ($this->attempt->status !== ExamAttemptStatus::InProgress) {
            $this->redirectRoute('student.attempts.submitted', $attempt, navigate: true);
        }
    }

    public function askSubmit(): void
    {
        $this->dialog()
            ->question('Submit final answers?', 'You will not be able to change your answers after submission.')
            ->confirm('Submit answers', 'confirmSubmit')
            ->cancel('Review again')
            ->persistent()
            ->send();
    }

    public function confirmSubmit(ExamAttemptService $attempts): void
    {
        $payload = $this->attempt->answers
            ->mapWithKeys(fn ($answer) => [
                $answer->question_id => $answer->question_option_id ?? $answer->open_text_answer,
            ])
            ->all();

        $attempts->submit($this->attempt, $payload);

        $this->redirectRoute('student.attempts.submitted', $this->attempt, navigate: true);
    }

    public function answerFor(int $questionId)
    {
        return $this->attempt->answers->firstWhere('question_id', $questionId);
    }
}; ?>

<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-sm text-zinc-500">{{ $attempt->exam->title }}</p>
        <h1 class="mt-1 text-3xl font-semibold text-zinc-950">Review your answers</h1>
        <p class="mt-2 text-sm text-zinc-600">Check your responses before final submission. You cannot edit after submitting.</p>
    </div>

    <x-card>
        <div class="space-y-4">
            @foreach ($attempt->exam->questions as $question)
                @php($answer = $this->answerFor($question->id))
                <div class="rounded-xl border border-zinc-200 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-zinc-950">{{ $loop->iteration }}. {{ $question->prompt }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $question->points }} point{{ $question->points === 1 ? '' : 's' }}</p>
                        </div>
                        <x-badge :text="$answer ? 'Answered' : 'Missing'" :color="$answer ? 'green' : 'red'" light />
                    </div>

                    <p class="mt-3 rounded-lg bg-zinc-50 px-3 py-2 text-sm leading-6 text-zinc-700">
                        @if ($question->type === QuestionType::MultipleChoice)
                            {{ $answer?->selectedOption?->text ?? 'No answer selected.' }}
                        @else
                            {{ $answer?->open_text_answer ?: 'No written answer.' }}
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <x-button text="Back to Questions" outline :href="route('student.attempts.show', $attempt)" navigate />
            <x-button text="Submit Final Answers" icon="paper-airplane" wire:click="askSubmit" loading="askSubmit" />
        </div>
    </x-card>
</div>
