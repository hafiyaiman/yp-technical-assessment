<?php

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.student')] class extends Component
{
    public ExamAttempt $attempt;

    public function mount(ExamAttempt $attempt): void
    {
        abort_unless(auth()->user()->can('view', $attempt), 403);

        $this->attempt = $attempt->load(['exam.subject']);
    }
}; ?>

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">
    <x-card>
        <div class="py-8 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full {{ $attempt->status === ExamAttemptStatus::Expired ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                {{ $attempt->status === ExamAttemptStatus::Expired ? '!' : '✓' }}
            </div>
            <h1 class="mt-6 text-3xl font-semibold text-zinc-950">
                {{ $attempt->status === ExamAttemptStatus::Expired ? 'Time expired' : 'Exam submitted' }}
            </h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-zinc-600">
                @if ($attempt->status === ExamAttemptStatus::Graded)
                    Your result is ready.
                @elseif ($attempt->status === ExamAttemptStatus::Expired)
                    This attempt was marked expired because it passed the server time limit.
                @else
                    Your answers were received. Open-text questions may need lecturer review before the final score is ready.
                @endif
            </p>

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <x-button text="Available Exams" outline :href="route('student.exams.index')" navigate />
                @if ($attempt->status === ExamAttemptStatus::Graded)
                    <x-button text="View Result" :href="route('student.results.show', $attempt)" navigate />
                @else
                    <x-button text="Past Results" :href="route('student.results.index')" navigate />
                @endif
            </div>
        </div>
    </x-card>
</div>
