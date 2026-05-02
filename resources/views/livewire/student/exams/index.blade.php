<?php

use App\Enums\ExamAttemptStatus;
use App\Models\Exam;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission('take-exams'), 403);
    }

    public function exams()
    {
        $student = auth()->user();

        if ($student->school_class_id === null) {
            return collect();
        }

        return Exam::query()
            ->visibleToStudent($student)
            ->with([
                'subject',
                'schoolClass',
                'questions',
                'attempts' => fn ($query) => $query->where('student_id', $student->id)->with('answers'),
            ])
            ->latest('published_at')
            ->get();
    }

    public function newExams()
    {
        return $this->exams()->filter(fn (Exam $exam) => $exam->attempts->isEmpty())->values();
    }

    public function inProgressExams()
    {
        return $this->exams()
            ->filter(fn (Exam $exam) => $exam->attempts->first()?->status === ExamAttemptStatus::InProgress)
            ->values();
    }

    public function pendingExams()
    {
        return $this->exams()
            ->filter(fn (Exam $exam) => in_array($exam->attempts->first()?->status, [ExamAttemptStatus::Submitted, ExamAttemptStatus::Expired], true))
            ->values();
    }

    public function gradedExams()
    {
        return $this->exams()
            ->filter(fn (Exam $exam) => $exam->attempts->first()?->status === ExamAttemptStatus::Graded)
            ->values();
    }
}; ?>

@php
    $renderExam = function ($exam) {
        $attempt = $exam->attempts->first();
        $answered = $attempt?->answers->count() ?? 0;
        $total = $exam->questions->count();
        $status = $attempt?->status;
    };
@endphp

<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Exam</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-dark-300">Choose a tab to see what needs action and where your results are.</p>
    </div>

    @if (auth()->user()->school_class_id === null)
        <x-card>
            <div class="py-10 text-center">
                <p class="text-lg font-semibold text-zinc-950">No class assigned yet</p>
                <p class="mt-2 text-sm text-zinc-500">Ask your lecturer to assign you to a class before taking exams.</p>
            </div>
        </x-card>
    @else
        <x-tab selected="new" scroll-on-mobile>
            <x-tab.items tab="new" title="New ({{ $this->newExams()->count() }})">
                <div class="space-y-3">
                    @forelse ($this->newExams() as $exam)
                        <x-student.exam-row :exam="$exam" />
                    @empty
                        <x-student.empty-state title="No new exams" description="New exams assigned to your class will appear here." />
                    @endforelse
                </div>
            </x-tab.items>

            <x-tab.items tab="progress" title="In Progress ({{ $this->inProgressExams()->count() }})">
                <div class="space-y-3">
                    @forelse ($this->inProgressExams() as $exam)
                        <x-student.exam-row :exam="$exam" />
                    @empty
                        <x-student.empty-state title="No exams in progress" description="Started exams that are not submitted yet will appear here." />
                    @endforelse
                </div>
            </x-tab.items>

            <x-tab.items tab="pending" title="Waiting Review ({{ $this->pendingExams()->count() }})">
                <div class="space-y-3">
                    @forelse ($this->pendingExams() as $exam)
                        <x-student.exam-row :exam="$exam" />
                    @empty
                        <x-student.empty-state title="Nothing waiting for review" description="Submitted exams waiting for lecturer marking will appear here." />
                    @endforelse
                </div>
            </x-tab.items>

            <x-tab.items tab="results" title="Results Ready ({{ $this->gradedExams()->count() }})">
                <div class="space-y-3">
                    @forelse ($this->gradedExams() as $exam)
                        <x-student.exam-row :exam="$exam" />
                    @empty
                        <x-student.empty-state title="No results ready" description="When a lecturer finishes marking, your result will appear here." />
                    @endforelse
                </div>
            </x-tab.items>
        </x-tab>
    @endif
</div>
