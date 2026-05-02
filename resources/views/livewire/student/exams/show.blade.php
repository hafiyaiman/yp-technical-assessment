<?php

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use App\Services\Exams\ExamAttemptService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Exam $exam;

    public function mount(Exam $exam): void
    {
        abort_unless(auth()->user()->hasPermission('take-exams') && $exam->canBeTakenBy(auth()->user()), 403);

        $this->exam = $exam->load(['schoolClass', 'subject', 'questions']);
    }

    public function start(ExamAttemptService $attempts): void
    {
        $attempt = $attempts->start(auth()->user(), $this->exam);

        if ($attempt->status === ExamAttemptStatus::InProgress) {
            $this->redirectRoute('student.attempts.show', $attempt, navigate: true);
            return;
        }

        $this->redirectRoute('student.attempts.submitted', $attempt, navigate: true);
    }
}; ?>

<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <x-card>
        <div class="space-y-6">
            <div>
                <x-badge :text="$exam->subject->name" color="gray" light />
                <h1 class="mt-4 text-3xl font-semibold tracking-normal text-zinc-950">{{ $exam->title }}</h1>
                <p class="mt-3 text-base leading-7 text-zinc-600">{{ $exam->instructions ?: 'Read each question carefully before submitting.' }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs font-medium uppercase text-zinc-500">Class</p>
                    <p class="mt-1 font-semibold text-zinc-950">{{ $exam->schoolClass->name }}</p>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs font-medium uppercase text-zinc-500">Questions</p>
                    <p class="mt-1 font-semibold text-zinc-950">{{ $exam->questions->count() }}</p>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs font-medium uppercase text-zinc-500">Time limit</p>
                    <p class="mt-1 font-semibold text-zinc-950">{{ $exam->duration_minutes }} minutes</p>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Once you start, the timer runs on the server. Keep this tab open until you submit.
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <x-button text="Back to Exams" flat :href="route('student.exams.index')" navigate />
                <x-button text="Start Exam" icon="play" wire:click="start" loading="start" />
            </div>
        </div>
    </x-card>
</div>
