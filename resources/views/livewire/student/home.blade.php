<?php

use App\Models\Exam;
use App\Models\ExamAttempt;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.student')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('take-exams'), 403);
    }

    public function availableCount(): int
    {
        $student = auth()->user();

        return $student->school_class_id === null
            ? 0
            : Exam::query()->visibleToStudent($student)->count();
    }

    public function submittedCount(): int
    {
        return ExamAttempt::query()->where('student_id', auth()->id())->count();
    }
}; ?>

<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <x-card>
        <div class="space-y-6">
            <div>
                <p class="text-sm font-medium text-zinc-500">Welcome, {{ auth()->user()->name }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-normal text-zinc-950">Student Home</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-600">
                    Keep this space simple. Use the Exam menu to view available tests and continue any exam in progress.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                    <p class="text-sm text-zinc-500">Available exams</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $this->availableCount() }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                    <p class="text-sm text-zinc-500">Attempts</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $this->submittedCount() }}</p>
                </div>
            </div>

            <x-button text="Go to Exam" icon="arrow-right" :href="route('student.exams.index')" navigate />
        </div>
    </x-card>
</div>
