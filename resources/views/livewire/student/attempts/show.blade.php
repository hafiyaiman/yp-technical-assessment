<?php

use App\Enums\ExamAttemptStatus;
use App\Enums\QuestionType;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamAttemptService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.student')] class extends Component
{
    public ExamAttempt $attempt;
    public int $current = 0;
    public array $answers = [];
    public ?string $savedAt = null;

    public function mount(ExamAttempt $attempt): void
    {
        abort_unless(auth()->user()->can('submit', $attempt), 403);

        $this->attempt = $attempt->load(['exam.subject', 'exam.questions.options', 'answers']);

        if ($this->attempt->status !== ExamAttemptStatus::InProgress) {
            $this->redirectRoute('student.attempts.submitted', $attempt, navigate: true);
            return;
        }

        if ($this->attempt->isExpired()) {
            $this->expire();
            return;
        }

        foreach ($this->attempt->answers as $answer) {
            $this->answers[$answer->question_id] = $answer->question_option_id ?? $answer->open_text_answer;
        }
    }

    public function updatedAnswers(mixed $value, string $questionId): void
    {
        app(ExamAttemptService::class)->saveAnswer($this->attempt, (int) $questionId, $value);
        $this->savedAt = now()->format('g:i A');
    }

    public function previous(): void
    {
        $this->current = max(0, $this->current - 1);
    }

    public function next(): void
    {
        $this->current = min($this->questions()->count() - 1, $this->current + 1);
    }

    public function goToQuestion(int $index): void
    {
        $this->current = max(0, min($this->questions()->count() - 1, $index));
    }

    public function review(): void
    {
        $this->redirectRoute('student.attempts.review', $this->attempt, navigate: true);
    }

    public function expire(): void
    {
        if ($this->attempt->status === ExamAttemptStatus::InProgress) {
            $this->attempt->update(['status' => ExamAttemptStatus::Expired]);
        }

        $this->redirectRoute('student.attempts.submitted', $this->attempt, navigate: true);
    }

    public function questions()
    {
        return $this->attempt->exam->questions->values();
    }

    public function question()
    {
        return $this->questions()->get($this->current);
    }

    public function answeredCount(): int
    {
        return collect($this->answers)->filter(fn ($answer) => filled($answer))->count();
    }
}; ?>

@php($question = $this->question())

<div
    class="px-3 py-4 sm:px-5 lg:px-6"
    x-data="{
        remaining: Math.max(0, Math.floor((new Date('{{ $attempt->expires_at->toIso8601String() }}') - new Date()) / 1000)),
        init() {
            const timer = setInterval(() => {
                this.remaining = Math.max(0, this.remaining - 1);
                if (this.remaining === 0) {
                    clearInterval(timer);
                    $wire.expire();
                }
            }, 1000);
        },
        time() {
            const minutes = Math.floor(this.remaining / 60).toString().padStart(2, '0');
            const seconds = (this.remaining % 60).toString().padStart(2, '0');
            return `${minutes}:${seconds}`;
        }
    }"
>
    <div class="mb-3 flex items-center justify-between border-b border-zinc-200 bg-white px-3 py-3 shadow-sm">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-zinc-950">{{ $attempt->exam->title }}</p>
            <p class="text-xs text-zinc-500">{{ $attempt->exam->subject->name }}</p>
        </div>
        <x-button text="Review & Submit" icon="paper-airplane" sm wire:click="review" />
    </div>

    <div class="grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)_210px]">
        <aside class="rounded-lg border border-zinc-200 bg-white p-3 shadow-sm">
            <div class="mb-3 flex items-center justify-between text-xs text-zinc-500">
                <span>Questions</span>
                <span>{{ $this->answeredCount() }}/{{ $this->questions()->count() }}</span>
            </div>

            <div class="grid grid-cols-5 gap-2 lg:grid-cols-4">
                @foreach ($this->questions() as $paletteQuestion)
                    @php($isAnswered = filled($answers[$paletteQuestion->id] ?? null))
                    @php($isCurrent = $loop->index === $current)
                    <button
                        type="button"
                        wire:click="goToQuestion({{ $loop->index }})"
                        class="h-9 rounded-md border text-xs font-semibold transition {{ $isCurrent ? 'border-zinc-950 bg-zinc-950 text-white' : ($isAnswered ? 'border-green-300 bg-green-50 text-green-800' : 'border-zinc-200 bg-white text-zinc-500 hover:border-zinc-400') }}"
                    >
                        {{ $loop->iteration }}
                    </button>
                @endforeach
            </div>

            <div class="mt-4 space-y-2 text-xs text-zinc-500">
                <div class="flex items-center gap-2"><span class="h-3 w-3 rounded border border-green-300 bg-green-50"></span> Answered</div>
                <div class="flex items-center gap-2"><span class="h-3 w-3 rounded border border-zinc-200 bg-white"></span> Not answered</div>
                <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-zinc-950"></span> Current</div>
            </div>
        </aside>

        <main>
            <x-card>
                <div class="mb-5">
                    <div class="flex items-center justify-between text-sm text-zinc-500">
                        <span>Question {{ $current + 1 }} of {{ $this->questions()->count() }}</span>
                        <span>{{ $savedAt ? "Saved {$savedAt}" : 'Autosave ready' }}</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-zinc-200">
                        <div class="h-2 rounded-full bg-zinc-950 transition-all" style="width: {{ (($current + 1) / max(1, $this->questions()->count())) * 100 }}%"></div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <x-badge :text="$question->type === QuestionType::MultipleChoice ? 'Multiple choice' : 'Open text'" color="gray" light />
                        <h2 class="mt-4 text-xl font-semibold leading-8 text-zinc-950 sm:text-2xl">{{ $question->prompt }}</h2>
                        <p class="mt-2 text-sm text-zinc-500">{{ $question->points }} point{{ $question->points === 1 ? '' : 's' }}</p>
                    </div>

                    @if ($question->type === QuestionType::MultipleChoice)
                        <div class="space-y-3">
                            @foreach ($question->options as $option)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-zinc-200 p-4 transition hover:border-zinc-400 hover:bg-zinc-50">
                                    <x-radio wire:model.live="answers.{{ $question->id }}" :value="$option->id" />
                                    <span class="text-base leading-7 text-zinc-800">{{ $option->text }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <x-textarea
                            wire:model.live.debounce.800ms="answers.{{ $question->id }}"
                            rows="8"
                            placeholder="Type your answer here..."
                            resize
                        />
                    @endif

                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <x-button text="Previous" outline wire:click="previous" :disabled="$current === 0" />
                        @if ($current + 1 < $this->questions()->count())
                            <x-button text="Save & Next" wire:click="next" />
                        @else
                            <x-button text="Review Answers" icon="check-circle" wire:click="review" />
                        @endif
                    </div>
                </div>
            </x-card>
        </main>

        <aside class="space-y-3">
            <x-card>
                <div class="text-center">
                    <p class="text-xs uppercase text-zinc-500">Time left</p>
                    <p class="mt-2 font-mono text-3xl font-semibold tabular-nums text-zinc-950" x-text="time()"></p>
                </div>
            </x-card>

            <x-card>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-zinc-500">Total</span><span class="font-semibold">{{ $this->questions()->count() }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Answered</span><span class="font-semibold text-green-700">{{ $this->answeredCount() }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Not Answered</span><span class="font-semibold">{{ $this->questions()->count() - $this->answeredCount() }}</span></div>
                    <div class="flex justify-between"><span class="text-zinc-500">Current</span><span class="font-semibold">{{ $current + 1 }}</span></div>
                </div>
            </x-card>

            <x-button text="Review & Submit" icon="paper-airplane" wire:click="review" class="w-full" />
        </aside>
    </div>
</div>
