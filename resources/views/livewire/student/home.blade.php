<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Enums\ExamAttemptStatus;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('take-exams'), 403);
    }

    public function availableCount(): int
    {
        $student = auth()->user();

        return $student->school_class_id === null ? 0 : Exam::query()->visibleToStudent($student)->count();
    }

    public function submittedCount(): int
    {
        return ExamAttempt::query()
            ->where('student_id', auth()->id())
            ->count();
    }

    public function inProgressAttempt(): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->with(['exam.subject'])
            ->where('student_id', auth()->id())
            ->where('status', ExamAttemptStatus::InProgress)
            ->latest()
            ->first();
    }

    public function latestResult(): ?ExamAttempt
    {
        return ExamAttempt::query()
            ->with(['exam.subject'])
            ->where('student_id', auth()->id())
            ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::Expired, ExamAttemptStatus::Graded])
            ->latest()
            ->first();
    }

    public function gradedCount(): int
    {
        return ExamAttempt::query()
            ->where('student_id', auth()->id())
            ->where('status', ExamAttemptStatus::Graded)
            ->count();
    }
}; ?>

<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-dark-600 dark:bg-dark-800">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-sm font-medium text-zinc-500 dark:text-dark-300">Welcome, {{ auth()->user()->name }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">Home</h1>
                <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-dark-300">
                    Your exams, progress, and results are collected here. Start with the most important action below.
                </p>
            </div>
            <x-button text="Open Exam" icon="academic-cap" :href="route('student.exams.index')" navigate />
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <x-stats :number="$this->availableCount()" title="Available Exams" icon="document-text" color="blue" animated />
        <x-stats :number="$this->submittedCount()" title="Attempts" icon="clipboard-document-check" color="purple" animated />
        <x-stats :number="$this->gradedCount()" title="Results Ready" icon="chart-bar-square" color="green" animated />
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <x-card>
            @php($inProgress = $this->inProgressAttempt())
            <div class="flex h-full flex-col justify-between gap-5">
                <div>
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">Continue exam</p>
                    @if ($inProgress)
                        <h2 class="mt-3 text-xl font-semibold text-zinc-950 dark:text-white">
                            {{ $inProgress->exam->title }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">{{ $inProgress->exam->subject->name }}
                            / expires {{ $inProgress->expires_at->diffForHumans() }}</p>
                    @else
                        <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-dark-300">No exam is currently in
                            progress.</p>
                    @endif
                </div>

                @if ($inProgress)
                    <x-button text="Continue" icon="arrow-right" :href="route('student.attempts.show', $inProgress)" navigate />
                @else
                    <x-button text="Browse Exams" icon="academic-cap" color="gray" outline :href="route('student.exams.index')"
                        navigate />
                @endif
            </div>
        </x-card>

        <x-card>
            @php($latest = $this->latestResult())
            <div class="flex h-full flex-col justify-between gap-5">
                <div>
                    <p class="text-sm font-semibold text-zinc-950 dark:text-white">Latest result</p>
                    @if ($latest)
                        <h2 class="mt-3 text-xl font-semibold text-zinc-950 dark:text-white">{{ $latest->exam->title }}
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">
                            {{ $latest->status === ExamAttemptStatus::Graded ? "{$latest->score} / {$latest->max_score} marks" : str($latest->status->value)->headline() }}
                        </p>
                    @else
                        <p class="mt-3 text-sm leading-6 text-zinc-500 dark:text-dark-300">Your submitted exams and
                            marks will appear in Results.</p>
                    @endif
                </div>

                <x-button text="View Results" icon="chart-bar-square" :color="$latest ? 'primary' : 'gray'" :outline="!$latest"
                    :href="route('student.results.index')" navigate />
            </div>
        </x-card>
    </section>
</div>
